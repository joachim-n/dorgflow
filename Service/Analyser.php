<?php

namespace Dorgflow\Service;

/**
 * Figures stuff out from user input and available data.
 */
class Analyser {

  protected $issue_number;

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
    if (isset($this->issue_number)) {
      return $this->issue_number;
    }

    // Try to deduce an issue number from the current branch.
    // TODO, no try to get a feature branch instead.
    $current_branch = $this->git_info->getCurrentBranch();

    // TODO: analysis should be in DataSource classes!
    $matches = [];
    preg_match("@^(?P<number>\d+)-@", $current_branch, $matches);
    if (!empty($matches['number'])) {
      $this->issue_number = $matches['number'];
      return $this->issue_number;
    }

    $this->issue_number = $this->user_input->getIssueNumber();

    if (!empty($issue_number)) {
      return $this->issue_number;
    }

    // Dev mode.
    /*
    if ($this->devel_mode) {
      return 2801423;
    }
    */

    throw new \Exception("Unable to find an issue number from command line parameter or current git branch.");
  }

  public function getCurrentProjectName() {
    // @todo caching?
    $working_dir = getcwd();

    // Special case for core; I for one have Drupal installed in lots of
    // funnily-named folders.
    // Drupal 8.
    if (file_exists($working_dir . "/core/index.php")) {
      return 'drupal';
    }
    // Drupal 7 and prior.
    if (file_exists($working_dir . "/index.php")) {
      return 'drupal';
    }

    // Get the module name.
    $current_module = basename($working_dir);

    // Allow for module folders to have a suffix. (E.g., I might have views-6
    // and views-7 in my sandbox folder.)
    $current_module = preg_replace("@-.*$@", '', $current_module);

    $this->current_project = $current_module;

    return $current_module;
  }

}
