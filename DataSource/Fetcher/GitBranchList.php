<?php

namespace Dorgflow\DataSource\Fetcher;

class GitBranchList implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetchData() {
    // TODO: check in right dir!

    // Get the branches that are reachable.
    $branch_list = shell_exec("git branch --merged");

    return $branch_list;
  }

}
