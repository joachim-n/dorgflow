<?php

namespace Dorgflow\Parser;

/**
 * Takes output from git branch command and parses it to data about branches.
 *
 * Feed this the output of:
 * @code
 * git branch --merged
 * @endcode
 */
class GitBranchList {

  protected $input;

  protected $branchList = [];

  protected $currentBranch;

  public function setInput($input) {
    $this->input = $input;
  }

  public function parse() {
    // Split the lines of the input.
    $git_branch_list = explode("\n", rtrim($this->input));
    //print_r($git_branch_list);

    $branch_names = [];
    foreach ($git_branch_list as $branch) {
      $matches = [];
      preg_match("@^(?P<mark>.)\s+(?P<name>\S+)@", $branch, $matches);

      if (!empty($matches['mark']) && $matches['mark'] == '*') {
        $this->currentBranch = $matches['name'];
      }
      //print_r(">$branch<");

      // Build a clean list of branch names.
      $branch_names[] = $matches['name'];
    }

    // Sort branch names by version number with the highest first. This accounts for a
    // situation where we're on a branch such as 7.x-3.x, and 7.x-2.x is a direct
    // ancestor because it's had no further development since branching.
    usort($branch_names, 'version_compare');

    $this->branchList = array_reverse($branch_names);
  }

  public function get($property_name) {
    return $this->{$property_name};
  }

}
