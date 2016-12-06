<?php

namespace Dorgflow\DataSource\Fetcher;

use Dorgflow\Situation;

/**
 * Interface for Fetchers.
 */
interface FetcherInterface {

  /**
   * Retrieve the data for this fetcher.
   *
   * @return
   *  Some sort of data.
   */
  public function fetchData(Situation $situation);

}
