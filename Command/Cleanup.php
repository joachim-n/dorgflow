<?php

namespace Dorgflow\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Deletes the current feature branch.
 */
class Cleanup extends SymfonyCommand implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('cleanup')
      ->setDescription('Deletes the current feature branch.')
      ->setHelp('Deletes the current feature branch.');
  }

  protected function setServices() {
    $this->git_info = $this->container->get('git.info');
    $this->waypoint_manager_branches = $this->container->get('waypoint_manager.branches');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->setServices();

    // Check git is clean.
    $clean = $this->git_info->gitIsClean();
    if (!$clean) {
      throw new \Exception("Git repository is not clean. Aborting.");
    }

    $master_branch = $this->waypoint_manager_branches->getMasterBranch();
    $feature_branch = $this->waypoint_manager_branches->getFeatureBranch();

    $master_branch_name = $master_branch->getBranchName();
    $feature_branch_name = $feature_branch->getBranchName();

    print "You are about to checkout branch $master_branch_name and DELETE branch $feature_branch_name!\n";
    $confirmation = readline("Please enter 'delete' to confirm:");

    if ($confirmation != 'delete') {
      print "Clean up aborted.\n";
      return 0;
    }

    $master_branch_name = $master_branch->getBranchName();
    shell_exec("git checkout $master_branch_name");

    shell_exec("git branch -D $feature_branch_name");

    return 0;

    // TODO: delete any patch files for this issue.
  }

}
