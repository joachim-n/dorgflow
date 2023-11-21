<?php

namespace Dorgflow\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Switches git to the master branch.
 */
#[\AllowDynamicProperties]
class SwitchMaster extends SymfonyCommand implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('master')
      ->setDescription('Checks out the master branch.')
      ->setHelp('Checks out the master branch that the current feature branch is branched from.');
  }

  protected function setServices() {
    $this->git_info = $this->container->get('git.info');
    $this->waypoint_manager_branches = $this->container->get('waypoint_manager.branches');
    $this->git_executor = $this->container->get('git.executor');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->setServices();

    // Check git is clean.
    $clean = $this->git_info->gitIsClean();
    if (!$clean) {
      throw new \Exception("Git repository is not clean. Aborting.");
    }

    $master_branch = $this->waypoint_manager_branches->getMasterBranch();
    $master_branch->gitCheckout();
  }

}
