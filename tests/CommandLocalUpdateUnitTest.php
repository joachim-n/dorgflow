<?php

namespace Dorgflow\Tests;

/**
 * Tests the local setup command.
 *
 * Run with:
 * @code
 *   vendor/bin/phpunit tests/CommandLocalUpdateTest.php
 * @endcode
 */
class CommandLocalUpdateUnitTest extends CommandTestBase {

  /*
  TODO:
  - feature branch doesn't exist
  - new patch on empty feature branch
  - new patch on feature branch with patches only
  - new patch on feature branch with only local commits
  - new patch on feature branch with patches, then local commits
  - new patch on feature branch that ends with local patch
  - no new patches
  - new patch doesn't apply
  - new patches, older one doesn't apply
  */

  /**
   * Tests the case where the feature branch can't be found.
   */
  public function testNoFeatureBranch() {
    $feature_branch = $this->getMockBuilder(\Dorgflow\Waypoint\FeatureBranch::class)
      ->disableOriginalConstructor()
      ->setMethods(['exists'])
      ->getMock();
    // Feature branch reports it doesn't exist.
    $feature_branch->method('exists')
      ->willReturn(FALSE);

    // Branch manager to provide the feature branch.
    $waypoint_manager_branches = $this->getMockBuilder(\Dorgflow\Service\WaypointManagerBranches::class)
      ->disableOriginalConstructor()
      ->setMethods(['getFeatureBranch'])
      ->getMock();
    $waypoint_manager_branches->method('getFeatureBranch')
      ->willReturn($feature_branch);

    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $container->set('git.info', $this->getMockGitInfoClean());
    $container->set('waypoint_manager.branches', $waypoint_manager_branches);
    // These won't get called, so don't need to mock anything.
    $container->set('waypoint_manager.patches', $this->getMockBuilder(StdClass::class));
    $container->set('git.executor', $this->getMockBuilder(StdClass::class));

    $command_tester = $this->setUpCommandTester($container, 'update', \Dorgflow\Command\LocalUpdate::class);

    $this->expectException(\Exception::class);

    $command_tester->execute([
      'command'  => 'update',
    ]);
  }

  /**
   * Tests a new patch that applies on an empty feature branch.
   */
  public function testNewPatchEmptyBranch() {
    // Set up one new patch on the issue.
    $patches = [];

    $patch = $this->getMockBuilder(\Dorgflow\Waypoint\Patch::class)
      ->disableOriginalConstructor()
      ->setMethods(['hasCommit', 'commitPatch', 'getPatchFilename'])
      ->getMock();
    // Patch is new: it has no commit.
    $patch->method('hasCommit')
      ->willReturn(FALSE);
    // We expect the patch will get committed (and that it will apply OK).
    $patch->expects($this->once())
      ->method('commitPatch')
      ->willReturn(TRUE);
    // The patch filename; needed for output message.
    $patch->method('getPatchFilename')
      ->willReturn('file-patch-0.patch');
    $patches[] = $patch;

    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $container->set('git.info', $this->getMockGitInfoClean());
    $container->set('waypoint_manager.branches', $this->getMockWaypointManagerFeatureBranchCurrent());
    $container->set('waypoint_manager.patches', $this->getMockWaypointManagerWithPatches($patches));
    $container->set('git.executor', $this->getMockBuilder(StdClass::class));

    $command_tester = $this->setUpCommandTester($container, 'update', \Dorgflow\Command\LocalUpdate::class);

    $command_tester->execute([
      'command'  => 'update',
    ]);

    // TODO: test user output
  }

  /**
   * Tests a new patch that applies on a feature branch with a patch.
   */
  public function testNewPatchBranchWithPatch() {
    // Set up two new patch on the issue, one which is already committed.
    $patches = [];

    // Already committed patch.
    $patch = $this->getMockBuilder(\Dorgflow\Waypoint\Patch::class)
      ->disableOriginalConstructor()
      ->setMethods(['hasCommit', 'commitPatch', 'getSHA'])
      ->getMock();
    // Patch is new: it has no commit.
    $patch->method('hasCommit')
      ->willReturn(TRUE);
    $patch->method('hasCommit')
      ->willReturn(TRUE);
    // The branch has no local commits, so the last committed patch is at the
    // feature branch tip.
    $patch->method('getSHA')
      ->willReturn('sha-feature');
    // We expect the patch will not get committed.
    $patch->expects($this->never())
      ->method('commitPatch');
    $patches[] = $patch;

    // New patch.
    $patch = $this->getMockBuilder(\Dorgflow\Waypoint\Patch::class)
      ->disableOriginalConstructor()
      ->setMethods(['hasCommit', 'commitPatch', 'getPatchFilename'])
      ->getMock();
    // Patch is new: it has no commit.
    $patch->method('hasCommit')
      ->willReturn(FALSE);
    // We expect the patch will get committed (and that it will apply OK).
    $patch->expects($this->once())
      ->method('commitPatch')
      ->willReturn(TRUE);
    // The patch filename; needed for output message.
    $patch->method('getPatchFilename')
      ->willReturn('file-patch-1.patch');
    $patches[] = $patch;

    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $container->set('git.info', $this->getMockGitInfoClean());
    $container->set('waypoint_manager.branches', $this->getMockWaypointManagerFeatureBranchCurrent());
    $container->set('waypoint_manager.patches', $this->getMockWaypointManagerWithPatches($patches));
    $container->set('git.executor', $this->getMockBuilder(StdClass::class));

    $command_tester = $this->setUpCommandTester($container, 'update', \Dorgflow\Command\LocalUpdate::class);

    $command_tester->execute([
      'command'  => 'update',
    ]);
  }

  /**
   * Tests a new patch that applies on a feature branch with local commits.
   */
  public function testNewPatchBranchWithLocalCommits() {
    // Set up two new patch on the issue, one which is already committed.
    $patches = [];

    // Already committed patch.
    $patch = $this->getMockBuilder(\Dorgflow\Waypoint\Patch::class)
      ->disableOriginalConstructor()
      ->setMethods(['hasCommit', 'commitPatch', 'getSHA'])
      ->getMock();
    // Patch is new: it has no commit.
    $patch->method('hasCommit')
      ->willReturn(TRUE);
    $patch->method('hasCommit')
      ->willReturn(TRUE);
    // The branch has local commits, so the last committed patch has an SHA
    // different from the feature branch tip.
    $patch->method('getSHA')
      ->willReturn('sha-patch-0');
    // We expect the patch will not get committed.
    $patch->expects($this->never())
      ->method('commitPatch');
    $patches[] = $patch;

    // New patch.
    $patch = $this->getMockBuilder(\Dorgflow\Waypoint\Patch::class)
      ->disableOriginalConstructor()
      ->setMethods(['hasCommit', 'commitPatch', 'getPatchFilename'])
      ->getMock();
    // Patch is new: it has no commit.
    $patch->method('hasCommit')
      ->willReturn(FALSE);
    // We expect the patch will get committed (and that it will apply OK).
    $patch->expects($this->once())
      ->method('commitPatch')
      ->willReturn(TRUE);
    // The patch filename; needed for output message.
    $patch->method('getPatchFilename')
      ->willReturn('file-patch-1.patch');
    $patches[] = $patch;

    // Git executor.
    $git_executor = $this->getMockBuilder(\Dorgflow\Service\GitExecutor::class)
      ->disableOriginalConstructor()
      ->setMethods(['createNewBranch', 'moveBranch'])
      ->getMock();
    // We expect the Git Exec to create a new branch with a forked branch name.
    $git_executor->expects($this->once())
      ->method('createNewBranch')
      ->with($this->matchesRegularExpression('/^123456-feature-forked-/'));
    // We expect the Git Exec to move the feature branch to the SHA of the last
    // committed patch.
    $git_executor->expects($this->once())
      ->method('moveBranch')
      ->with($this->isType('string'), 'sha-patch-0');

    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $container->set('git.info', $this->getMockGitInfoClean());
    $container->set('waypoint_manager.branches', $this->getMockWaypointManagerFeatureBranchCurrent());
    $container->set('waypoint_manager.patches', $this->getMockWaypointManagerWithPatches($patches));
    $container->set('git.executor', $git_executor);

    $command_tester = $this->setUpCommandTester($container, 'update', \Dorgflow\Command\LocalUpdate::class);

    $command_tester->execute([
      'command'  => 'update',
    ]);
  }

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
   * Creates a mock branch service whose feature branch is current.
   *
   * @return
   *  The mocked waypoint_manager.branches service object. It will provide a
   *  feature branch which:
   *  - reports it exists
   *  - reports it is current
   *  - has a branch name of '123456-feature'
   *  - has an SHA of 'sha-feature'
   */
  protected function getMockWaypointManagerFeatureBranchCurrent() {
    $feature_branch = $this->getMockBuilder(\Dorgflow\Waypoint\FeatureBranch::class)
      ->disableOriginalConstructor()
      ->setMethods(['exists', 'isCurrentBranch', 'getSHA', 'getBranchName'])
      ->getMock();
    $feature_branch->method('exists')
      ->willReturn(TRUE);
    $feature_branch->method('isCurrentBranch')
      ->willReturn(TRUE);
    $feature_branch->method('getBranchName')
      ->willReturn('123456-feature');
    $feature_branch->method('getSHA')
      ->willReturn('sha-feature');

    $waypoint_manager_branches = $this->getMockBuilder(\Dorgflow\Service\WaypointManagerBranches::class)
      ->disableOriginalConstructor()
      ->setMethods(['getFeatureBranch'])
      ->getMock();
    $waypoint_manager_branches->method('getFeatureBranch')
      ->willReturn($feature_branch);

    return $waypoint_manager_branches;
  }

  /**
   * Creates a mock patch manager that will provide the given array of patches.
   *
   * @param $patches
   *  An array of mock patch objects.
   *
   * @return
   *  The mocked waypoint_manager.patches service object.
   */
  protected function getMockWaypointManagerWithPatches($patches) {
    $waypoint_manager_patches = $this->getMockBuilder(\Dorgflow\Service\WaypointManagerPatches::class)
      ->disableOriginalConstructor()
      ->setMethods(['setUpPatches'])
      ->getMock();
    $waypoint_manager_patches->method('setUpPatches')
      ->willReturn($patches);

    return $waypoint_manager_patches;
  }

}
