<?php

namespace Dorgflow\DataSource\Fetcher;

use Dorgflow\Situation;

class CurrentProject implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetchData(Situation $situation, $parameters) {
    $working_dir = getcwd();
    return $working_dir;
  }

}
