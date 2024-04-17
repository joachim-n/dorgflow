<?php

namespace Dorgflow\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Dorgflow\DependencyInjection\ContainerAwareTrait;

/**
 * Provides the diff command.
 */
#[\AllowDynamicProperties]
class Diff extends SymfonyCommand {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('diff')
      ->setDescription('Shows a git diff to the master branch.')
      ->setHelp('Shows the changes made on the feature branch, compared to the master branch.');
  }

  /**
   * {@inheritdoc}
   */
  protected function setServices() {
    $this->git_info = $this->container->get('git.info');
    $this->waypoint_manager_branches = $this->container->get('waypoint_manager.branches');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->setServices();

    $io = new SymfonyStyle($input, $output);

    // Check git is clean.
    $clean = $this->git_info->gitIsClean();
    if (!$clean) {
      $io->note('Git is not clean: the diff will include your uncommitted changes.');
    }

    $master_branch = $this->waypoint_manager_branches->getMasterBranch();

    $diff = $this->git_info->diffMasterBranch($master_branch->getBranchName());

    $io->text($diff);
  }

}
