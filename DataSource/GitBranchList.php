<?php

namespace Dorgflow\DataSource;

class GitBranchList extends DataSourceBase {

  protected $branchList = [];

  public function getBranchList() {
    return $this->branchList;
  }

}
