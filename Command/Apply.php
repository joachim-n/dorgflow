<?php

namespace Dorgflow\Command;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Applies the current feature branch to the master branch as a squash merge.
 */
class Apply extends CommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('apply')
      ->setDescription('Applies the current feature branch to the master branch.')
      ->setHelp('Applies the diff of the current feature branch to the master branch, so it can be committed.');
  }

  /**
   * Creates an instance of this command, injecting services from the container.
   */
  static public function create(ContainerBuilder $container) {
    return new static(
      $container->get('git.info'),
      $container->get('waypoint_manager.branches'),
      $container->get('git.executor'),
      $container->get('analyser')
    );
  }

  function __construct($git_info, $waypoint_manager_branches, $git_executor, $analyser) {
    $this->git_info = $git_info;
    $this->waypoint_manager_branches = $waypoint_manager_branches;
    $this->git_executor = $git_executor;
    $this->analyser = $analyser;
  }

  public function execute() {
    // Check git is clean.
    $clean = $this->git_info->gitIsClean();
    if (!$clean) {
      throw new \Exception("Git repository is not clean. Aborting.");
    }

    // Create branches.
    $master_branch = $this->waypoint_manager_branches->getMasterBranch();
    $feature_branch = $this->waypoint_manager_branches->getFeatureBranch();

    // If the feature branch is not current, abort.
    if (!$feature_branch->exists()) {
      throw new \Exception("Could not find a feature branch. Aborting.");
    }
    if (!$feature_branch->isCurrentBranch()) {
      throw new \Exception(strtr("Detected feature branch !branch, but it is not the current branch. Aborting.", [
        '!branch' => $feature_branch->getBranchName(),
      ]));
    }

    // @todo check that the feature branch tip is the same as the most recent patch
    // from d.org

    // Check out the master branch.
    $this->git_executor->checkOutBranch($master_branch->getBranchName());
    // Perform a squash merge from the feature branch: in other words, all the
    // changes on the feature branch are now staged on master.
    $this->git_executor->squashMerge($feature_branch->getBranchName());

    print strtr("Changes from feature branch !feature-branch are now applied and staged on branch !master-branch.\n", [
      '!feature-branch' => $feature_branch->getBranchName(),
      '!master-branch'  => $master_branch->getBranchName(),
    ]);
    print strtr("You should now commit this, using the command from the issue on drupal.org: https://www.drupal.org/node/!id#drupalorg-issue-credit-form.\n", [
      '!id' => $this->analyser->deduceIssueNumber(),
    ]);
  }

}
