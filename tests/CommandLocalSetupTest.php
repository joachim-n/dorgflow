<?php

namespace Dorgflow\Tests;

use Dorgflow\Application;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Console\Tester\CommandTester;

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
class CommandLocalSetupTest extends CommandTestBase {

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

    $command_tester = $this->setUpCommandTester($container, 'setup', \Dorgflow\Command\LocalSetup::class);

    $this->expectException(\Exception::class);

    $command_tester->execute([
      'command'  => 'setup',
    ]);
  }

  /**
   * Test the command bails when the master branch is not current.
   */
  public function testNotOnMasterBranch() {
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $git_info = $this->createMock(\Dorgflow\Service\GitInfo::class);
    // Git is clean so the command proceeds.
    $git_info->method('gitIsClean')
      ->willReturn(TRUE);
    $branch_list = [
      '8.x-2.x' => 'sha',
      'some-branch-name' => 'sha',
      'something-else' => 'sha',
    ];
    $git_info->method('getBranchList')
      ->willReturn($branch_list);
    $git_info->method('getBranchListReachable')
      ->willReturn($branch_list);
    // The master branch is not current -- we're on some other branch that's not
    // for an issue.
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

    // The requested issue number comes from user input.
    $analyser = $this->createMock(\Dorgflow\Service\Analyser::class);
    $analyser->method('deduceIssueNumber')
      ->willReturn(123456);
    $container->set('analyser', $analyser);

    // Need the real service for this, as we want the command to get the branch
    // object from it, based on the mocked git.info service.
    $container
      ->register('waypoint_manager.branches', \Dorgflow\Service\WaypointManagerBranches::class)
      ->addArgument(new Reference('git.info'))
      ->addArgument(new Reference('drupal_org'))
      ->addArgument(new Reference('git.executor'))
      ->addArgument(new Reference('analyser'));

    $container->set('waypoint_manager.patches', $this->getMockBuilder(\Dorgflow\Service\WaypointManagerPatches::class));

    $command_tester = $this->setUpCommandTester($container, 'setup', \Dorgflow\Command\LocalSetup::class);

    $this->expectException(\Exception::class);

    $command_tester->execute([
      'command'  => 'setup',
    ]);
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
    $branch_list = [
      '8.x-2.x' => 'sha',
      // Feature branch already exists.
      // Only the issue number part counts to determine this; the rest of the
      // branch name should not matter, so this is intentionally different
      // from the issue node title.
      '123456-some-branch-name' => 'sha',
      'some-other-branch' => 'sha',
    ];
    $git_info->method('getBranchList')
      ->willReturn($branch_list);
    $git_info->method('getBranchListReachable')
      ->willReturn($branch_list);
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

    // The git executor be called only to check out the feature branch.
    $git_executor = $this->createMock(\Dorgflow\Service\GitExecutor::class);
    $git_executor->expects($this->once())
      ->method('checkOutBranch')
      ->with($this->equalTo('123456-some-branch-name'));
    // No branches will be created or patches applied.
    $git_executor->expects($this->never())->method('createNewBranch');
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

    $command_tester = $this->setUpCommandTester($container, 'setup', \Dorgflow\Command\LocalSetup::class);

    $command_tester->execute([
      'command'  => 'setup',
    ]);
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
    $branch_list = [
      '8.3.x' => 'sha',
    ];
    $git_info->method('getBranchList')
      ->willReturn($branch_list);
    $git_info->method('getBranchListReachable')
      ->willReturn($branch_list);
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

    $command_tester = $this->setUpCommandTester($container, 'setup', \Dorgflow\Command\LocalSetup::class);

    $command_tester->execute([
      'command'  => 'setup',
    ]);
  }

  /**
   * Test setup on an issue with patches.
   */
  public function testIssueWithPatches() {
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $prophet = new \Prophecy\Prophet;
    $git_info = $prophet->prophesize();
    $git_info->willExtend(\Dorgflow\Service\GitInfo::class);

    // Git is clean so the command proceeds.
    $git_info->gitIsClean()->willReturn(TRUE);

    $git_info->getCurrentBranch()->willReturn('8.3.x');

    $branch_list = [
      '8.3.x' => 'sha',
    ];
    $git_info->getBranchList()->willReturn($branch_list);
    $git_info->getBranchListReachable()->willReturn($branch_list);

    $container->set('git.info', $git_info->reveal());

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
        'applies' => TRUE,
        'expected' => 'apply',
      ],
      // Not displayed; will be skipped.
      1 => [
        'fid' => 205,
        'cid' => 405,
        'index' => 5,
        'filename' => 'fix-5.patch',
        'display' => FALSE,
        'expected' => 'skip',
      ],
      // Not a patch; will be skipped.
      2 => [
        'fid' => 206,
        'cid' => 406,
        'index' => 6,
        'filename' => 'fix-5.not.patch.txt',
        'display' => TRUE,
        'expected' => 'skip',
      ],
      3 => [
        'fid' => 210,
        'cid' => 410,
        'index' => 10,
        'filename' => 'fix-10.patch',
        'display' => TRUE,
        'applies' => TRUE,
        'expected' => 'apply',
      ],
    ];
    $this->setUpDrupalOrgExpectations($drupal_org, $patch_file_data);
    $container->set('drupal_org', $drupal_org);

    $git_executor = $this->createMock(\Dorgflow\Service\GitExecutor::class);
    // A new branch will be created.
    $git_executor->expects($this->once())
      ->method('createNewBranch')
      ->with(
        $this->callback(function($subject) use ($git_info) {
          // Creating a new branch changes what git_info will return as the
          // current branch.
          // (Yes, horrible mishmash of two mocking systems.)
          $git_info->getCurrentBranch()->willReturn($subject);

          return ($subject == '123456-Terribly-awful-bug');
        }),
        $this->equalTo(TRUE)
      );

    $this->setUpGitExecutorPatchExpectations($git_executor, $patch_file_data);
    $container->set('git.executor', $git_executor);

    $container->set('commit_message', $this->createMock(\Dorgflow\Service\CommitMessageHandler::class));
    $container->set('git.log', $this->createMock(\Dorgflow\Service\GitLog::class));

    // Add real versions of any remaining services not yet registered.
    $this->completeServiceContainer($container);

    $command_tester = $this->setUpCommandTester($container, 'setup', \Dorgflow\Command\LocalSetup::class);

    $command_tester->execute([
      'command'  => 'setup',
    ]);
  }

}
