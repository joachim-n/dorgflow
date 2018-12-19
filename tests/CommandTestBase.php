<?php

namespace Dorgflow\Tests;

use Dorgflow\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Base class for command tests.
 */
abstract class CommandTestBase extends \PHPUnit\Framework\TestCase {

  /**
   * Creates a mock git.info service that will state that git is clean.
   *
   * @return
   *  The mocked git.info service object.
   */
  protected function getMockGitInfoClean() {
    $git_info = $this->getMockBuilder(\Dorgflow\Service\GitInfo::class)
      ->disableOriginalConstructor()
      ->setMethods(['gitIsClean'])
      ->getMock();
    $git_info->method('gitIsClean')
      ->willReturn(TRUE);

    return $git_info;
  }

  /**
   * Sets up the mock drupal_org service with the given patch file data.
   *
   * @param $drupal_org
   *  The mock drupal_org service.
   * @param $patch_file_data
   *  An array of data for the patch files. The key is the filefield delta; each
   *  item is an array with the following properties:
   *    - 'fid': The file entity ID.
   *    - 'cid': The comment entity ID for this file.
   *    - 'index': The comment index.
   *    - 'filename': The patch filename.
   *    - 'display': Boolean indicating whether the file is displayed.
   */
  protected function setUpDrupalOrgExpectations($drupal_org, $patch_file_data) {
    $getIssueFileFieldItems_return = [];
    $getFileEntity_value_map = [];
    $getPatchFile_value_map = [];

    foreach ($patch_file_data as $patch_file_data_item) {
      $file_field_item = (object) [
        'file' => (object) [
          'uri' => 'https://www.drupal.org/api-d7/file/' . $patch_file_data_item['fid'],
          'id' => $patch_file_data_item['fid'],
          'resource' => 'file',
          'cid' => $patch_file_data_item['cid'],
        ],
        'display' => $patch_file_data_item['display'],
        'index' => $patch_file_data_item['index'],
      ];
      $getIssueFileFieldItems_return[] = $file_field_item;

      $getFileEntity_value_map[] = [
        $patch_file_data_item['fid'],
        // For dummy file entities, we only need the url property.
        (object) ['url' => $patch_file_data_item['filename']]
      ];

      $getPatchFile_value_map[] = [
        $patch_file_data_item['filename'],
        // The contents of the patch file.
        'patch-file-data-' . $patch_file_data_item['fid']
      ];
    }

    $drupal_org->method('getIssueFileFieldItems')
      ->willReturn($getIssueFileFieldItems_return);
    $drupal_org->expects($this->any())
      ->method('getFileEntity')
      ->will($this->returnValueMap($getFileEntity_value_map));
    $drupal_org->expects($this->any())
      ->method('getPatchFile')
      ->will($this->returnValueMap($getPatchFile_value_map));
  }

  /**
   * Sets up the mock git.executor service with the given patch file data.
   *
   * @param $git_executor
   *  The mock git.executor service.
   * @param $patch_file_data
   *  An array of data for the patch files. The key is the filefield delta; each
   *  item is an array with the following properties:
   *    - 'fid': The file entity ID.
   *    - 'cid': The comment entity ID for this file.
   *    - 'index': The comment index.
   *    - 'filename': The patch filename.
   *    - 'display': Boolean indicating whether the file is displayed.
   *    - 'applies': (optional) Boolean indicating whether the patch should
   *      cause the git executor to report it applies. Only needed if the value
   *      of 'expected' is 'apply'.
   *    - 'expected': The expected outcome for this patch. One of:
   *      - 'skip': The patch will not be downloaded or applied.
   *      - 'apply': The git exectutor will attempt to apply the patch.
   */
  protected function setUpGitExecutorPatchExpectations($git_executor, $patch_file_data) {
    // The total number of patches.
    $patch_count = 0;
    // The number of patches that will apply.
    $applies_count = 0;
    // A map of the dummy patch file contents (that is, the parameter that will
    // be passed to applyPatch()) to a boolean indicating whether the patch
    // applies or not.
    $applyPatch_map = [];

    foreach ($patch_file_data as $patch_file_data_item) {
      // If the patch is expected to skip, do so.
      if ($patch_file_data_item['expected'] == 'skip') {
        continue;
      }

      $patch_count++;

      $patch_file_contents = 'patch-file-data-' . $patch_file_data_item['fid'];

      if (!empty($patch_file_data_item['applies'])) {
        $applies_count++;

        $applyPatch_map[$patch_file_contents] = TRUE;
      }
      else {
        $applyPatch_map[$patch_file_contents] = FALSE;
      }
    }

    // For each patch, the master branch files will be checked out.
    // (Technically we should verify this checks out the right branch, but that
    // would mean an extra faffy parameter to this helper method.)
    $git_executor
      ->expects($this->exactly($patch_count))
      ->method('checkOutBranch');
    $git_executor
      ->expects($this->exactly($patch_count))
      ->method('moveToBranch');

    // For each patch we expect to be attempted to apply, the patch file
    // contents will be applied.
    $git_executor
      ->expects($this->exactly($patch_count))
      ->method('applyPatch')
      ->with($this->callback(function($subject) use ($applyPatch_map) {
        // Statics inside closures are apparently iffy, but this appears to
        // work...!
        static $patch_contents;
        if (!isset($patch_contents)) {
          $patch_contents = array_keys($applyPatch_map);
        }

        $expected_parameter = array_shift($patch_contents);
        return $expected_parameter == $subject;
      }))
      ->will($this->returnCallback(function ($patch_file_data) use ($applyPatch_map) {
        return $applyPatch_map[$patch_file_data];
      }));
    // Each patch that applies will be committed.
    $git_executor
      ->expects($this->exactly($applies_count))
      ->method('commit');
  }

  /**
   * Add any services to the container that are not yet registered on it.
   *
   * NOTE: currently only takes care of commit_message and the waypoint
   * managers.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *  The service container.
   */
  protected function completeServiceContainer(ContainerBuilder $container) {
    // TODO: add all the other services, but so far these are always mocked, to
    // YAGNI.

    if (!$container->has('commit_message')) {
      $container
        ->register('commit_message', \Dorgflow\Service\CommitMessageHandler::class)
        ->addArgument(new Reference('analyser'));
    }

    if (!$container->has('waypoint_manager.branches')) {
      $container
        ->register('waypoint_manager.branches', \Dorgflow\Service\WaypointManagerBranches::class)
        ->addArgument(new Reference('git.info'))
        ->addArgument(new Reference('drupal_org'))
        ->addArgument(new Reference('git.executor'))
        ->addArgument(new Reference('analyser'));
    }

    if (!$container->has('waypoint_manager.patches')) {
      $container
        ->register('waypoint_manager.patches', \Dorgflow\Service\WaypointManagerPatches::class)
        ->addArgument(new Reference('commit_message'))
        ->addArgument(new Reference('drupal_org'))
        ->addArgument(new Reference('git.log'))
        ->addArgument(new Reference('git.executor'))
        ->addArgument(new Reference('analyser'))
        ->addArgument(new Reference('waypoint_manager.branches'));
    }
  }

  /**
   * Gets the CommandTester object to run a given command.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *  The DI container.
   * @param $name
   *  The name of the command as registered in the console application.
   * @param $class
   *  The Command class.
   *
   * @return \Symfony\Component\Console\Tester\CommandTester
   *  The command tester object.
   */
  protected function setUpCommandTester(ContainerBuilder $container, $name, $class) {
    $container
      ->register("command.{$name}", $class)
      ->addMethodCall('setContainer', [new Reference('service_container')]);

    $application = new Application();
    $application->add($container->get("command.{$name}"));

    $command = $application->find($name);
    $command_tester = new CommandTester($command);

    return $command_tester;
  }

}
