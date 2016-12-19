<?php

namespace Dorgflow\DataSource;

class GitBranchList extends DataSourceBase {

  public function getBranchList() {
    return $this->data;
  }

}
