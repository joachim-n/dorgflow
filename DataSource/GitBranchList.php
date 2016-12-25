<?php

namespace Dorgflow\DataSource;

class GitBranchList extends DataSourceBase {

  /**
   * Returns a list of all the git branches which are currently reachable.
   *
   * @return
   *  An array of branch names keyed by the SHA of the tip commit.
   */
  public function getBranchList() {
    return $this->data;
  }

}
