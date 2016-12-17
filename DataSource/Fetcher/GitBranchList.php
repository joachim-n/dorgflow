<?php

namespace Dorgflow\DataSource\Fetcher;

use Dorgflow\Situation;

/**
 * Gets a list of all git branches which are currently reachable.
 */
class GitBranchList implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetchData(Situation $situation, $parameters) {
    // TODO: check in right dir!

    $branch_list = [];

    // Get the list of local branches as 'SHA BRANCHNAME'.
    $refs = shell_exec("git for-each-ref refs/heads/ --format='%(objectname) %(refname:short)'");
    foreach (explode("\n", trim($refs)) as $line) {
      list($sha, $branch_name) = explode(' ', $line);

      $output = '';
      // Exit value is 0 if true, 1 if false.
      $return_var = '';
      exec("git merge-base --is-ancestor $branch_name HEAD", $output, $return_var);

      if ($return_var === 0) {
        $branch_list[] = $branch_name;
      }
    }

    return $branch_list;
  }

}
