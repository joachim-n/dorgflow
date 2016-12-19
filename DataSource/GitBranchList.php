<?php

namespace Dorgflow\DataSource;

class GitBranchList extends DataSourceBase {

  protected $branchList = [];

  protected $currentBranch;

  public function getBranchList() {
    return $this->branchList;
  }

  public function getCurrentBranch() {
    // TODO: move to a new datasource, using:
    // git symbolic-ref --short HEAD

    return $this->currentBranch;
  }

}
