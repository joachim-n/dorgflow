<?php

namespace Dorgflow\Tests;

use Symfony\Component\DependencyInjection\Reference;

/**
 * System test for the local setup command.
 *
 * This mocks raw input, that is, git info, git branches, and drupal.org data.
 *
 * Run with:
 * @code
 *   vendor/bin/phpunit tests/CommandLocalSetupTest.php
 * @endcode
 */
class CommandLocalSetupTest extends \PHPUnit\Framework\TestCase {

  /**
   * Test the command bails when git is not clean.
   */
  public function testGitUnclean() {
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $git_info = $this->getMockBuilder(\Dorgflow\Service\GitInfo::class)
      ->disableOriginalConstructor()
      ->setMethods(['gitIsClean'])
      ->getMock();
    $git_info->method('gitIsClean')
      ->willReturn(FALSE);

    $container->set('git.info', $git_info);
    // These won't get called, so don't need to mock anything.
    $container->set('waypoint_manager.branches', $this->getMockBuilder(StdClass::class));
    $container->set('waypoint_manager.patches', $this->getMockBuilder(StdClass::class));

    $command = \Dorgflow\Command\LocalSetup::create($container);

    try {
      $command->execute();

      $this->fail("Expected Exception for unclean git not thrown.");
    }
    catch (\Exception $e) {
      // Pass.
    }
  }

  /**
   * Test the command bails when the master branch is not current.
   */
  public function testNoMasterBranch() {
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $git_info = $this->createMock(\Dorgflow\Service\GitInfo::class);
    // Git is clean so the command proceeds.
    $git_info->method('gitIsClean')
      ->willReturn(TRUE);
    $git_info->method('getBranchList')
      ->willReturn([
        '8.x-2.x' => 'sha',
        'some-branch-name' => 'sha',
        'something-else' => 'sha',
      ]);
    // The master branch is not current.
    $git_info->method('getCurrentBranch')
      ->willReturn('some-branch-name');
    $container->set('git.info', $git_info);

    // The git executor should not be called at all.
    $git_executor = $this->createMock(\Dorgflow\Service\GitExecutor::class);
    $git_executor->expects($this->never())->method($this->anything());
    $container->set('git.executor', $git_executor);

    // Drupal.org API should not be called at all.
    $drupal_org = $this->createMock(\Dorgflow\Service\DrupalOrg::class);
    $drupal_org->expects($this->never())->method($this->anything());
    $container->set('drupal_org', $drupal_org);

    $container->set('analyser', $this->createMock(\Dorgflow\Service\Analyser::class));

    // Need the real service for this, as we want the command to get the branch
    // object from it, based on the mocked git.info service.
    $container
      ->register('waypoint_manager.branches', \Dorgflow\Service\WaypointManagerBranches::class)
      ->addArgument(new Reference('git.info'))
      ->addArgument(new Reference('drupal_org'))
      ->addArgument(new Reference('git.executor'))
      ->addArgument(new Reference('analyser'));

    $container->set('waypoint_manager.patches', $this->getMockBuilder(\Dorgflow\Service\WaypointManagerPatches::class));

    $command = \Dorgflow\Command\LocalSetup::create($container);

    try {
      $command->execute();

      $this->fail("Expected Exception for master branch not current not thrown.");
    }
    catch (\Exception $e) {
      // Pass.
    }
  }

  /**
   * Test the command bails when the feature branch exists.
   */
  public function testFeatureBranchExists() {
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $git_info = $this->createMock(\Dorgflow\Service\GitInfo::class);
    // Git is clean so the command proceeds past this check.
    $git_info->method('gitIsClean')
      ->willReturn(TRUE);
    $git_info->method('getBranchList')
      ->willReturn([
        '8.x-2.x' => 'sha',
        // Feature branch already exists.
        // Only the issue number part counts to determine this; the rest of the
        // branch name should not matter, so this is intentionally different
        // from the issue node title.
        '123456-some-branch-name' => 'sha',
        'some-other-branch' => 'sha',
      ]);
    // The master branch is current so we proceed past master branch discovery.
    $git_info->method('getCurrentBranch')
      ->willReturn('8.x-2.x');
    $container->set('git.info', $git_info);

    $analyser = $this->createMock(\Dorgflow\Service\Analyser::class);
    $analyser->method('deduceIssueNumber')
      ->willReturn(123456);
    $container->set('analyser', $analyser);

    $drupal_org = $this->createMock(\Dorgflow\Service\DrupalOrg::class);
    $drupal_org->method('getIssueNodeTitle')
      ->willReturn('Terribly awful bug');
    // Issue file fields should not be requested.
    $drupal_org->expects($this->never())->method('getIssueFileFieldItems');
    $container->set('drupal_org', $drupal_org);

    // The git executor should not be called at all.
    $git_executor = $this->createMock(\Dorgflow\Service\GitExecutor::class);
    $git_executor->expects($this->never())->method($this->anything());
    $container->set('git.executor', $git_executor);

    $container
      ->register('waypoint_manager.branches', \Dorgflow\Service\WaypointManagerBranches::class)
      ->addArgument(new Reference('git.info'))
      ->addArgument(new Reference('drupal_org'))
      ->addArgument(new Reference('git.executor'))
      ->addArgument(new Reference('analyser'));

    $waypoint_manager_patches = $this->createMock(\Dorgflow\Service\WaypointManagerPatches::class);
    $container->set('waypoint_manager.patches', $waypoint_manager_patches);

    $command = \Dorgflow\Command\LocalSetup::create($container);

    try {
      $command->execute();

      $this->fail("Expected Exception for existing feature branch not thrown.");
    }
    catch (\Exception $e) {
      // Pass.
    }
  }

  /**
   * Test setup on an issue with no patches.
   */
  public function testIssueNoPatches() {
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $git_info = $this->createMock(\Dorgflow\Service\GitInfo::class);
    // Git is clean so the command proceeds.
    $git_info->method('gitIsClean')
      ->willReturn(TRUE);
    // Master branch is current.
    $git_info->method('getCurrentBranch')
      ->willReturn('8.3.x');
    $git_info->method('getBranchList')
      ->willReturn(['8.3.x' => 'sha']);
    $container->set('git.info', $git_info);

    $analyser = $this->createMock(\Dorgflow\Service\Analyser::class);
    $analyser->method('deduceIssueNumber')
      ->willReturn(123456);
    $container->set('analyser', $analyser);

    $drupal_org = $this->createMock(\Dorgflow\Service\DrupalOrg::class);
    $drupal_org->method('getIssueNodeTitle')
      ->willReturn('Terribly awful bug');
    // No issue file fields.
    $drupal_org->method('getIssueFileFieldItems')
      ->willReturn([]);
    // Patch files will not be requested.
    $drupal_org->expects($this->never())->method('getFileEntity');
    $drupal_org->expects($this->never())->method('getPatchFile');
    $container->set('drupal_org', $drupal_org);

    $git_executor = $this->createMock(\Dorgflow\Service\GitExecutor::class);
    // A new branch will be created.
    $git_executor->expects($this->once())
      ->method('createNewBranch')
      ->with($this->equalTo('123456-Terribly-awful-bug'), $this->equalTo(TRUE));
    // No patches will be applied.
    $git_executor->expects($this->never())->method('checkOutFiles');
    $git_executor->expects($this->never())->method('applyPatch');
    $git_executor->expects($this->never())->method('commit');

    $container->set('git.executor', $git_executor);

    $container
      ->register('waypoint_manager.branches', \Dorgflow\Service\WaypointManagerBranches::class)
      ->addArgument(new Reference('git.info'))
      ->addArgument(new Reference('drupal_org'))
      ->addArgument(new Reference('git.executor'))
      ->addArgument(new Reference('analyser'));

    $waypoint_manager_patches = $this->createMock(\Dorgflow\Service\WaypointManagerPatches::class);
    $container->set('waypoint_manager.patches', $waypoint_manager_patches);

    $command = \Dorgflow\Command\LocalSetup::create($container);

    $command->execute();
  }

  /**
   * Test setup on an issue with patches.
   */
  public function testIssueWithPatches() {
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $git_info = $this->createMock(\Dorgflow\Service\GitInfo::class);
    // Git is clean so the command proceeds.
    $git_info->method('gitIsClean')
      ->willReturn(TRUE);
    // Master branch is current.
    $git_info->method('getCurrentBranch')
      ->willReturn('8.3.x');
    $git_info->method('getBranchList')
      ->willReturn(['8.3.x' => 'sha']);
    $container->set('git.info', $git_info);

    $analyser = $this->createMock(\Dorgflow\Service\Analyser::class);
    $analyser->method('deduceIssueNumber')
      ->willReturn(123456);
    $container->set('analyser', $analyser);

    $drupal_org = $this->createMock(\Dorgflow\Service\DrupalOrg::class);
    $drupal_org->method('getIssueNodeTitle')
      ->willReturn('Terribly awful bug');
    $patch_file_data = [
      0 => [
        'fid' => 200,
        'cid' => 400,
        'index' => 1,
        'filename' => 'fix-1.patch',
        'display' => TRUE,
      ],
      // Not displayed; will be skipped.
      1 => [
        'fid' => 205,
        'cid' => 405,
        'index' => 5,
        'filename' => 'fix-5.patch',
        'display' => FALSE,
      ],
      // Not a patch; will be skipped.
      2 => [
        'fid' => 206,
        'cid' => 406,
        'index' => 6,
        'filename' => 'fix-5.not.patch.txt',
        'display' => TRUE,
      ],
      3 => [
        'fid' => 210,
        'cid' => 410,
        'index' => 10,
        'filename' => 'fix-10.patch',
        'display' => TRUE,
      ],
    ];
    $this->setUpDrupalOrgExpectations($drupal_org, $patch_file_data);
    $container->set('drupal_org', $drupal_org);

    $git_executor = $this->createMock(\Dorgflow\Service\GitExecutor::class);
    // A new branch will be created.
    $git_executor->expects($this->once())
      ->method('createNewBranch')
      ->with($this->equalTo('123456-Terribly-awful-bug'), $this->equalTo(TRUE));
    // Both patches will be applied.
    // For each patch, the master branch files will be checked out.
    $git_executor
      ->expects($this->exactly(2))
      ->method('checkOutFiles')
      ->with('8.3.x');
    // For each patch, the patch file contents will be applied.
    $git_executor
      ->expects($this->exactly(2))
      ->method('applyPatch')
      ->withConsecutive(
        ['patch-file-data-200'],
        ['patch-file-data-210']
      )
      // Patch file applies correctly.
      ->willReturn(TRUE);
    $git_executor
      ->expects($this->exactly(2))
      ->method('commit');
    $container->set('git.executor', $git_executor);

    $container->set('commit_message', $this->createMock(\Dorgflow\Service\CommitMessageHandler::class));
    $container->set('git.log', $this->createMock(\Dorgflow\Service\GitLog::class));

    // Use the real branches manager service.
    $container
      ->register('waypoint_manager.branches', \Dorgflow\Service\WaypointManagerBranches::class)
      ->addArgument(new Reference('git.info'))
      ->addArgument(new Reference('drupal_org'))
      ->addArgument(new Reference('git.executor'))
      ->addArgument(new Reference('analyser'));

    // Use the real patches manager service.
    $container
      ->register('waypoint_manager.patches', \Dorgflow\Service\WaypointManagerPatches::class)
      ->addArgument(new Reference('commit_message'))
      ->addArgument(new Reference('drupal_org'))
      ->addArgument(new Reference('git.log'))
      ->addArgument(new Reference('git.executor'))
      ->addArgument(new Reference('waypoint_manager.branches'));

    $command = \Dorgflow\Command\LocalSetup::create($container);

    $command->execute();
  }

  /**
   * Sets up the mock drupal_org service with the given patch file data.
   *
   * @param $drupal_org
   *  The mock drupal_org service.
   * @param $patch_file_data
   *  An array of data for the patch files.
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

}
