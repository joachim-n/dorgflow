<?php

namespace Dorgflow\DataSource\Fetcher;

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
  public function fetchData();

}
