<?php

namespace Dorgflow\Service;

/**
 * Figures stuff out from user input and available data.
 */
class Analyser {

  function __construct($git_info, $user_input) {
    $this->git_info = $git_info;
    $this->user_input = $user_input;
  }

  /**
   * Figures out the issue number in question, from input or current branch.
   *
   * @return int
   *  The issue number, which is the nid of the drupal.org issue node.
   */
  public function deduceIssueNumber() {
    /*
    // TODO: cache?
    if (isset($this->issue_number)) {
      return $this->issue_number;
    }
    */

    // Try to deduce an issue number from the current branch.
    // TODO, no try to get a feature branch instead.
    $current_branch = $this->git_info->getCurrentBranch();

    // TODO: analysis should be in DataSource classes!
    $matches = [];
    preg_match("@^(?P<number>\d+)-@", $current_branch, $matches);
    if (!empty($matches['number'])) {
      return $matches['number'];
    }

    $issue_number = $this->user_input->getIssueNumber();

    if (!empty($issue_number)) {
      return $issue_number;
    }

    // Dev mode.
    /*
    if ($this->devel_mode) {
      return 2801423;
    }
    */

    throw new \Exception("Unable to find an issue number from command line parameter or current git branch.");
  }


}
