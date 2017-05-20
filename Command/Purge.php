<?php

namespace Dorgflow\Command;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Deletes ALL feature branches and files for issues which are fixed.
 */
class Purge extends CommandBase {

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
    foreach ($issues_to_clean_up as $issue_number => $info) {
      print " - issue: $issue_number\n   branch name: {$info['branch']}\n   committed in: {$info['message']}.\n";
    }

    $count = count($issues_to_clean_up);
    $confirmation = readline("Please enter 'delete' to confirm DELETION of {$count} branches:");

    if ($confirmation != 'delete') {
      print "Clean up aborted.\n";
      return;
    }

    foreach ($issues_to_clean_up as $issue_number => $info) {
      shell_exec("git branch -D {$info['branch']}");
    }
  }

  protected function getIssueCommit($issue_number) {
    // TODO: move to service.
    $git_log = shell_exec("git rev-list {$this->master_branch_name} --grep={$issue_number} --pretty=oneline");
    return $git_log;
  }

}
