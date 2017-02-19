<?php

namespace Dorgflow\Tests;

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
      ->addArgument(new \Symfony\Component\DependencyInjection\Reference('git.info'))
      ->addArgument(new \Symfony\Component\DependencyInjection\Reference('drupal_org'))
      ->addArgument(new \Symfony\Component\DependencyInjection\Reference('git.executor'))
      ->addArgument(new \Symfony\Component\DependencyInjection\Reference('analyser'));

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
      ->addArgument(new \Symfony\Component\DependencyInjection\Reference('git.info'))
      ->addArgument(new \Symfony\Component\DependencyInjection\Reference('drupal_org'))
      ->addArgument(new \Symfony\Component\DependencyInjection\Reference('git.executor'))
      ->addArgument(new \Symfony\Component\DependencyInjection\Reference('analyser'));

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
      ->addArgument(new \Symfony\Component\DependencyInjection\Reference('git.info'))
      ->addArgument(new \Symfony\Component\DependencyInjection\Reference('drupal_org'))
      ->addArgument(new \Symfony\Component\DependencyInjection\Reference('git.executor'))
      ->addArgument(new \Symfony\Component\DependencyInjection\Reference('analyser'));

    $waypoint_manager_patches = $this->createMock(\Dorgflow\Service\WaypointManagerPatches::class);
    $container->set('waypoint_manager.patches', $waypoint_manager_patches);

    $command = \Dorgflow\Command\LocalSetup::create($container);

    $command->execute();
  }

}
