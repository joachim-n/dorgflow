<?php

namespace Dorgflow\DataSource\Fetcher;

use Dorgflow\Situation;

class GitCurrentBranch implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetchData(Situation $situation, $parameters) {
    return shell_exec("git symbolic-ref --short -q HEAD");
  }

}
