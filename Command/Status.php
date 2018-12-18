<?php

namespace Dorgflow\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * TODO.
 */
class Status extends SymfonyCommand implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('status')
      ->setDescription('Shows current branch status.')
      ->setHelp('Shows the current branch status.');
  }

  protected function setServices() {
    $this->git_info = $this->container->get('git.info');
    $this->waypoint_manager_branches = $this->container->get('waypoint_manager.branches');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->setServices();

    // Create branch objects.
    $feature_branch = $this->waypoint_manager_branches->getFeatureBranch();
    $master_branch = $this->waypoint_manager_branches->getMasterBranch();

    if ($feature_branch->exists() && $feature_branch->isCurrentBranch()) {
      $output->writeln(strtr("<info>Feature branch: !feature-branch.</info>", [
        '!feature-branch' => $feature_branch->getBranchName(),
      ]));
      $output->writeln(strtr("<info>Master branch: !master-branch.</info>", [
        '!master-branch'  => $master_branch->getBranchName(),
      ]));
    }
    else {
      $output->writeln(strtr("<info>Master branch: !master-branch.</info>", [
        '!master-branch'  => $master_branch->getBranchName(),
      ]));
    }
  }

}
