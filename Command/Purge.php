<?php

namespace Dorgflow\Command;

use Dorgflow\Console\ItemList;
use Dorgflow\Console\DefinitionList;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Deletes ALL feature branches and files for issues which are fixed.
 */
class Purge extends SymfonyCommand implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('purge')
      ->setDescription('Deletes all feature branches whose issues are committed to the master branch.')
      ->setHelp('Deletes all feature branches whose issues are committed to the master branch.');
  }

  protected function setServices() {
    $this->git_info = $this->container->get('git.info');
    $this->waypoint_manager_branches = $this->container->get('waypoint_manager.branches');
    $this->analyser = $this->container->get('analyser');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->setServices();

    $this->master_branch_name = $this->waypoint_manager_branches->getMasterBranch()->getBranchName();

    $branch_list = $this->git_info->getBranchList();

    $issues_to_clean_up = [];

    foreach ($branch_list as $branch_name => $sha) {
      $issue_number = $this->analyser->extractIssueNumberFromBranch($branch_name);

      // Skip the branch if it's not for an issue.
      if (empty($issue_number)) {
        continue;
      }

      $issue_commit = $this->getIssueCommit($issue_number);

      // Skip the branch if we can't find a commit for it.
      if (empty($issue_commit)) {
        continue;
      }

      // TODO get patch and interdiff files too.

      // TODO! Bug! in the case of a follow-up, an issue can have more than one
      // commit!
      list($sha, $message) = explode(' ', rtrim($issue_commit), 2);

      $issues_to_clean_up[$issue_number] = [
        'branch' => $branch_name,
        'message' => $message,
        'sha' => $sha,
      ];
    }

    if (empty($issues_to_clean_up)) {
      print "No branches to clean up.\n";
      return;
    }

    // Sort by issue number.
    // (TODO: sort by date of commit?)
    ksort($issues_to_clean_up);

    print "You are about to DELETE the following branches!\n";
    $list = new ItemList($output);
    foreach ($issues_to_clean_up as $issue_number => $info) {
      $nested_list = $list->getNestedListItem(DefinitionList::class);
      $nested_list->setDefinitionFormatterStyle(new \Symfony\Component\Console\Formatter\OutputFormatterStyle(
        NULL, NULL, ['bold']
      ));

      $nested_list->addItem("issue", $issue_number);
      $nested_list->addItem('branch name', $info['branch']);
      $nested_list->addItem('committed in', $info['message']);

      $list->addItem($nested_list);
    }
    $list->render();

    $helper = $this->getHelper('question');

    $count = count($issues_to_clean_up);
    $question = new Question("Please enter 'delete' to confirm DELETION of {$count} branches:");
    if ($helper->ask($input, $output, $question) != 'delete') {
      $output->writeln('Clean up aborted.');
      return;
    }

    foreach ($issues_to_clean_up as $issue_number => $info) {
      shell_exec("git branch -D {$info['branch']}");
      $output->writeln("Deleted branch {$info['branch']}.");
    }
  }

  protected function getIssueCommit($issue_number) {
    // TODO: move to service.
    $git_log = shell_exec("git rev-list {$this->master_branch_name} --grep={$issue_number} --pretty=oneline");
    return $git_log;
  }

}
