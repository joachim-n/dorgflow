<?php

namespace Dorgflow\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Applies the current feature branch to the master branch as a squash merge.
 */
class Apply extends Command implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('apply')
      ->setDescription('Applies the current feature branch to the master branch.')
      ->setHelp('Applies the diff of the current feature branch to the master branch, so it can be committed.');
  }

  protected function setServices() {
    $this->git_info = $this->container->get('git.info');
    $this->waypoint_manager_branches = $this->container->get('waypoint_manager.branches');
    $this->git_executor = $this->container->get('git.executor');
    $this->analyser = $this->container->get('analyser');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->setServices();

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
