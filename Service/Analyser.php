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
   * A user input value, that is, given as a command line parameter, takes
   * precedence over the current branch.
   *
   * @return int
   *  The issue number, which is the nid of the drupal.org issue node.
   *
   * @throws \Exception
   *  Throws an exception if no issue number can be found from any input.
   */
  public function deduceIssueNumber() {
    if (isset($this->issue_number)) {
      return $this->issue_number;
    }

    // Try to get an issue number from user input.
    // This comes first to allow commands to override the current branch with
    // input.
    $this->issue_number = $this->user_input->getIssueNumber();

    if (!empty($this->issue_number)) {
      return $this->issue_number;
    }

    // Try to deduce an issue number from the current branch.
    $current_branch = $this->git_info->getCurrentBranch();
    $this->issue_number = $this->extractIssueNumberFromBranch($current_branch);

    if (!empty($this->issue_number)) {
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

  /**
   * Determine an issue number from a git branch name.
   *
   * @param string $branch_name
   *  The branch name.
   *
   * @return
   *  An issue number, or NULL if none is found.
   */
  public function extractIssueNumberFromBranch($branch_name) {
    $matches = [];
    preg_match("@^(?P<number>\d+)-@", $branch_name, $matches);
    if (!empty($matches['number'])) {
      $issue_number = $matches['number'];
      return $issue_number;
    }
  }

  public function getCurrentProjectName() {
    // @todo caching?
    $repo_base_dir = $this->getRepoBaseDir();

    // Special case for core; I for one have Drupal installed in lots of
    // funnily-named folders.
    // Drupal 8.
    if (file_exists($repo_base_dir . "/core/index.php")) {
      return 'drupal';
    }
    // Drupal 7 and prior.
    if (file_exists($repo_base_dir . "/index.php")) {
      return 'drupal';
    }

    // Get the module name.
    $current_module = basename($repo_base_dir);

    // Allow for module folders to have a suffix. (E.g., I might have views-6
    // and views-7 in my sandbox folder.)
    $current_module = preg_replace("@-.*$@", '', $current_module);

    $this->current_project = $current_module;

    return $current_module;
  }

  /**
   * Gets the base directory for the repository.
   *
   * @return string
   *   The absolute path to the base directory of the repository.
   */
  protected function getRepoBaseDir() {
    $dir = getcwd();
    while (strlen($dir) > 1) {
      if (file_exists("$dir/.git/config")) {
        break;
      }
      $dir = dirname($dir);
    }
    return $dir;
  }

}
