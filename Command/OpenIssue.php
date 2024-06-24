<?php

namespace Dorgflow\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Dorgflow\DependencyInjection\ContainerAwareTrait;
use Dorgflow\Service\Analyser;

/**
 * Opens the d.org issue page for the current feature branch.
 *
 * Expects the system to have an `open` command which supports URLs.
 */
class OpenIssue extends Command {

  use ContainerAwareTrait;

  protected Analyser $analyser;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('open')
      ->setDescription('Opens the issue for the current feature branch.')
      ->setHelp('Uses the system `open` command to open the issue for the current feature branch in the default browser.');
  }

  protected function setServices() {
    $this->analyser = $this->container->get('analyser');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->setServices();

    $issue_number = $this->analyser->deduceIssueNumber();

    exec('open https://www.drupal.org/node/' . $issue_number);

    return 0;
  }

}
