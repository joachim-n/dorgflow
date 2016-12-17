<?php

namespace Dorgflow\DataSource;

class GitCurrentBranch extends DataSourceBase {

  public function getCurrentBranch() {
    return $this->data;
  }

}
