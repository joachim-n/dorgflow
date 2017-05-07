<?php

namespace Dorgflow\Command;

/*
test symfony command.

https://iamcode.guru/symfony-console-and-dependency-injection/

*/

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class SymfCommand extends Command implements ContainerAwareInterface {

  use ContainerAwareTrait;

  protected function configure() {
    $this
      // the name of the command (the part after "bin/console")
      ->setName('symfony')

      // the short description shown while running "php bin/console list"
      ->setDescription('test command.')

      // the full command description shown when running the command with
      // the "--help" option
      ->setHelp('This command is a test.');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    dump($this->container);
    //$git_info = $this->container->get('git.info');
    //dump($git_info->getCurrentBranch());

    $output->writeln('The command works!');
  }

}
