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
class CommandLocalUpdateTest extends \PHPUnit_Framework_TestCase {

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

    $command = new \Dorgflow\Command\LocalUpdate(
      $this->getMockGitInfoClean(),
      $waypoint_manager_branches,
      // We don't need these services, but the command expects them.
      // Passing NULL also means the test fails if these services are called.
      NULL,
      NULL
    );

    try {
      $command->execute();
      $this->fail("The exception was not thrown.");
    }
    catch (\Exception $e) {
      // Why is there no pass() method? WTF?
      $this->assertTrue(TRUE, "The exception was thrown as expected.");
    }
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

    $waypoint_manager_patches = $this->getMockBuilder(\Dorgflow\Service\WaypointManagerPatches::class)
      ->disableOriginalConstructor()
      ->setMethods(['setUpPatches'])
      ->getMock();
    $waypoint_manager_patches->method('setUpPatches')
      ->willReturn($patches);

    $command = new \Dorgflow\Command\LocalUpdate(
      // Mock services that allow the command to pass its sanity checks.
      $this->getMockGitInfoClean(),
      $this->getMockWaypointManagerFeatureBranchCurrent(),
      $waypoint_manager_patches,
      NULL
    );

    $command->execute();

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

    $command = new \Dorgflow\Command\LocalUpdate(
      // Mock services that allow the command to pass its sanity checks.
      $this->getMockGitInfoClean(),
      $this->getMockWaypointManagerFeatureBranchCurrent(),
      $this->getMockWaypointManagerWithPatches($patches),
      NULL
    );

    $command->execute();
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
   *  feature branch which reports it exists, is current, and returns a SHA of
   *  'sha-feature'.
   */
  protected function getMockWaypointManagerFeatureBranchCurrent() {
    $feature_branch = $this->getMockBuilder(\Dorgflow\Waypoint\FeatureBranch::class)
      ->disableOriginalConstructor()
      ->setMethods(['exists', 'isCurrentBranch', 'getSHA'])
      ->getMock();
    $feature_branch->method('exists')
      ->willReturn(TRUE);
    $feature_branch->method('isCurrentBranch')
      ->willReturn(TRUE);
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
