<?php

namespace Dorgflow\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Provides the status command.
 */
#[\AllowDynamicProperties]
class Status extends SymfonyCommand implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('status')
      ->setDescription('Shows a status summary.')
      ->setHelp('Shows the names of the detected master and feature branches.');
  }

  /**
   * {@inheritdoc}
   */
  protected function setServices() {
    $this->git_info = $this->container->get('git.info');
    $this->waypoint_manager_branches = $this->container->get('waypoint_manager.branches');
    $this->waypoint_manager_patches = $this->container->get('waypoint_manager.patches');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->setServices();

    $io = new SymfonyStyle($input, $output);

    $master_branch = $this->waypoint_manager_branches->getMasterBranch();
    $feature_branch = $this->waypoint_manager_branches->getFeatureBranch();

    $io->text(
      strtr("Master branch detected as <info>@branch</info>.", [
        '@branch' => $master_branch->getBranchName(),
      ])
    );

    if ($feature_branch->exists()) {
      $io->text(
        strtr("Feature branch detected as <info>@branch</info>.", [
          '@branch' => $feature_branch->getBranchName(),
        ])
      );
    }

    // TODO: show whether the branch is up to date with d.org.
    // $patch = $this->waypoint_manager_patches->getMostRecentPatch();
    // if ($patch) {

    // }

    $clean = $this->git_info->gitIsClean();
    if (!$clean) {
      $io->text('You have uncommitted changes.');
    }

    return 0;
  }

}
