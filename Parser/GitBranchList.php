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
    $branch_list = explode("\n", $this->input);
    print_r($branch_list);
    
    // Sort the lines by version number with the highest first. This accounts for a
    // situation where we're on a branch such as 7.x-3.x, and 7.x-2.x is a direct
    // ancestor because it's had no further development since branching.
    usort($branch_list, 'version_compare');
    $branch_list = array_reverse($branch_list);
    
  }

  public function get($property_name) {

  }

}
