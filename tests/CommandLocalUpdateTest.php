<?php

namespace Dorgflow\Tests;

use Symfony\Component\DependencyInjection\Reference;

/**
 * System test for the local update command.
 *
 * This mocks raw input, that is, git info, git branches, and drupal.org data.
 *
 * Run with:
 * @code
 *   vendor/bin/phpunit tests/CommandLocalUpdateTest.php
 * @endcode
 */
class CommandLocalUpdateTest extends CommandTestBase {

  /**
   * Test the command bails when git is not clean.
   */
  public function testGitUnclean() {
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $git_info = $this->createMock(\Dorgflow\Service\GitInfo::class);
    $git_info->method('gitIsClean')
      ->willReturn(FALSE);

    $container->set('git.info', $git_info);
    // These won't get called, so don't need to mock anything.
    $container->set('waypoint_manager.branches', $this->getMockBuilder(StdClass::class));
    $container->set('waypoint_manager.patches', $this->getMockBuilder(StdClass::class));
    $container->set('git.executor', $this->getMockBuilder(StdClass::class));

    $command = \Dorgflow\Command\LocalUpdate::create($container);

    try {
      $command->execute();

      $this->fail("Expected Exception for unclean git not thrown.");
    }
    catch (\Exception $e) {
      // Pass.
    }
  }

  /**
   * Tests the case where the feature branch can't be found.
   */
  public function testNoFeatureBranch() {
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $git_info = $this->createMock(\Dorgflow\Service\GitInfo::class);
    // Git is clean so the command proceeds.
    $git_info->method('gitIsClean')
      ->willReturn(TRUE);
    $git_info->method('getBranchList')
      ->willReturn([
        // There is no feature branch.
        '8.x-2.x' => 'sha',
        'some-branch-name' => 'sha',
        'something-else' => 'sha',
      ]);
    $container->set('git.info', $git_info);

    // The analyser returns an issue number.
    $analyser = $this->createMock(\Dorgflow\Service\Analyser::class);
    $analyser->method('deduceIssueNumber')
      ->willReturn(123456);
    $container->set('analyser', $analyser);

    // The git executor should not be called at all.
    $git_executor = $this->createMock(\Dorgflow\Service\GitExecutor::class);
    $git_executor->expects($this->never())->method($this->anything());
    $container->set('git.executor', $git_executor);

    // Drupal.org API should not be called at all.
    $drupal_org = $this->createMock(\Dorgflow\Service\DrupalOrg::class);
    $drupal_org->expects($this->never())->method($this->anything());
    $container->set('drupal_org', $drupal_org);

    // Need the real service for this, as we want the command to get the branch
    // object from it, based on the mocked git.info service.
    $container
      ->register('waypoint_manager.branches', \Dorgflow\Service\WaypointManagerBranches::class)
      ->addArgument(new Reference('git.info'))
      ->addArgument(new Reference('drupal_org'))
      ->addArgument(new Reference('git.executor'))
      ->addArgument(new Reference('analyser'));

    $container->set('waypoint_manager.patches', $this->getMockBuilder(\Dorgflow\Service\WaypointManagerPatches::class));

    $command = \Dorgflow\Command\LocalUpdate::create($container);

    try {
      $command->execute();
      $this->fail("The exception was not thrown.");
    }
    catch (\Exception $e) {
      $this->assertTrue(TRUE, "The exception was thrown as expected.");
    }
  }

  /**
   * Tests the case where the feature branch isn't current.
   */
  public function testNotOnFeatureBranch() {
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $git_info = $this->createMock(\Dorgflow\Service\GitInfo::class);
    // Git is clean so the command proceeds.
    $git_info->method('gitIsClean')
      ->willReturn(TRUE);
    $git_info->method('getBranchList')
      ->willReturn([
        // There is a feature branch.
        '123456-terrible-bug' => 'sha-feature',
        '8.x-2.x' => 'sha',
        'some-branch-name' => 'sha',
        'something-else' => 'sha',
      ]);
    // Master branch is current rather than the feature branch.
    $git_info->method('getCurrentBranch')
      ->willReturn('8.x-2.x');
    $container->set('git.info', $git_info);

    // The analyser returns an issue number.
    $analyser = $this->createMock(\Dorgflow\Service\Analyser::class);
    $analyser->method('deduceIssueNumber')
      ->willReturn(123456);
    $container->set('analyser', $analyser);

    // The git executor should not be called at all.
    $git_executor = $this->createMock(\Dorgflow\Service\GitExecutor::class);
    $git_executor->expects($this->never())->method($this->anything());
    $container->set('git.executor', $git_executor);

    // Drupal.org API should not be called at all.
    $drupal_org = $this->createMock(\Dorgflow\Service\DrupalOrg::class);
    $drupal_org->expects($this->never())->method($this->anything());
    $container->set('drupal_org', $drupal_org);

    // Need the real service for this, as we want the command to get the branch
    // object from it, based on the mocked git.info service.
    $container
      ->register('waypoint_manager.branches', \Dorgflow\Service\WaypointManagerBranches::class)
      ->addArgument(new Reference('git.info'))
      ->addArgument(new Reference('drupal_org'))
      ->addArgument(new Reference('git.executor'))
      ->addArgument(new Reference('analyser'));

    $container->set('waypoint_manager.patches', $this->getMockBuilder(\Dorgflow\Service\WaypointManagerPatches::class));

    $command = \Dorgflow\Command\LocalUpdate::create($container);

    try {
      $command->execute();
      $this->fail("The exception was not thrown.");
    }
    catch (\Exception $e) {
      $this->assertTrue(TRUE, "The exception was thrown as expected.");
    }
  }

  /**
   * Tests the case the feature branch has nothing and there are new patches.
   */
  public function testEmptyFeatureBranchNewPatches() {
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $git_info = $this->createMock(\Dorgflow\Service\GitInfo::class);
    // Git is clean so the command proceeds.
    $git_info->method('gitIsClean')
      ->willReturn(TRUE);
    $git_info->method('getBranchList')
      ->willReturn([
        // There is a feature branch, and its SHA is the same as the master
        // branch.
        '123456-terrible-bug' => 'sha-master',
        '8.3.x' => 'sha-master',
        'some-branch-name' => 'sha',
        'something-else' => 'sha',
      ]);
    // Feature branch is current.
    $git_info->method('getCurrentBranch')
      ->willReturn('123456-terrible-bug');
    $container->set('git.info', $git_info);

    $git_log = $this->createMock(\Dorgflow\Service\GitLog::class);
    // Feature branch log is empty.
    $git_log->method('getFeatureBranchLog')
      ->willReturn([]);
    $container->set('git.log', $git_log);

    // The analyser returns an issue number.
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

    $container->set('commit_message', $this->createMock(\Dorgflow\Service\CommitMessageHandler::class));

    $git_executor = $this->createMock(\Dorgflow\Service\GitExecutor::class);
    // No new branches will be created.
    $git_executor->expects($this->never())
      ->method('createNewBranch');

    // Both patches will be applied.
    $this->setUpGitExecutorPatchExpectations($git_executor, $patch_file_data);
    $container->set('git.executor', $git_executor);

    // Add real versions of any remaining services not yet registered.
    $this->completeServiceContainer($container);

    $command = \Dorgflow\Command\LocalUpdate::create($container);

    $command->execute();
  }

  /**
   * Tests a prior patch that failed to apply is not applied again.
   */
  public function testFeatureBranchFailingPatch() {
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $git_info = $this->createMock(\Dorgflow\Service\GitInfo::class);
    // Git is clean so the command proceeds.
    $git_info->method('gitIsClean')
      ->willReturn(TRUE);
    $git_info->method('getBranchList')
      ->willReturn([
        // There is a feature branch.
        '123456-terrible-bug' => 'sha-feature',
        '8.3.x' => 'sha-master',
        'some-branch-name' => 'sha',
        'something-else' => 'sha',
      ]);
    // Feature branch is current.
    $git_info->method('getCurrentBranch')
      ->willReturn('123456-terrible-bug');
    $container->set('git.info', $git_info);

    $git_log = $this->createMock(\Dorgflow\Service\GitLog::class);
    // Feature branch log has a commit for the second patch on the issue, since
    // the first one failed.
    $git_log->method('getFeatureBranchLog')
      ->willReturn([
        'sha-feature' => [
          'sha' => 'sha-feature',
          'message' => "Patch from Drupal.org. Comment: 10; URL: http://url.com/1234; file: applied.patch; fid: 210. Automatic commit by dorgflow.",
        ],
      ]);
    $container->set('git.log', $git_log);

    // The analyser returns an issue number.
    $analyser = $this->createMock(\Dorgflow\Service\Analyser::class);
    $analyser->method('deduceIssueNumber')
      ->willReturn(123456);
    $container->set('analyser', $analyser);

    $drupal_org = $this->createMock(\Dorgflow\Service\DrupalOrg::class);
    $drupal_org->method('getIssueNodeTitle')
      ->willReturn('Terribly awful bug');
    $patch_file_data = [
      0 => [
        // A patch that previously failed to apply.
        'fid' => 200,
        'cid' => 400,
        'index' => 1,
        'filename' => 'failing.patch',
        'display' => TRUE,
        // We expect that this will not be attempted again.
        'expected' => 'skip',
      ],
      1 => [
        // Patch has previously been applied.
        // This is the condition for the prior failing patch to not be attempted
        // again.
        'fid' => 210,
        'cid' => 410,
        'index' => 10,
        'filename' => 'applied.patch',
        'display' => TRUE,
        'expected' => 'skip',
      ],
      2 => [
        // New patch.
        'fid' => 220,
        'cid' => 420,
        'index' => 20,
        'filename' => 'new.patch',
        'display' => TRUE,
        'applies' => TRUE,
        'expected' => 'apply',
      ],
    ];
    $this->setUpDrupalOrgExpectations($drupal_org, $patch_file_data);
    $container->set('drupal_org', $drupal_org);

    $git_executor = $this->createMock(\Dorgflow\Service\GitExecutor::class);
    // No new branches will be created.
    $git_executor->expects($this->never())
      ->method('createNewBranch');
    // Only the new patch file will be applied.
    $this->setUpGitExecutorPatchExpectations($git_executor, $patch_file_data);
    $container->set('git.executor', $git_executor);

    // Add real versions of any remaining services not yet registered.
    $this->completeServiceContainer($container);

    $command = \Dorgflow\Command\LocalUpdate::create($container);

    $command->execute();
  }

}
