<?php

namespace Dorgflow\DataSource;

abstract class DataSourceBase {

  protected $data;

  /**
   * Constructor.
   *
   * Sets up the fetcher object of the same class name as this, if the class
   * exists. May be overridden with a parameter, for testing purposes.
   */
  public function __construct(Fetcher\FetcherInterface $fetcher = NULL) {
    if (empty($fetcher)) {
      $reflect = new \ReflectionClass($this);
      $short_name = $reflect->getShortName();
      $fetcher_name = "Dorgflow\\DataSource\\Fetcher\\$short_name";
      if (class_exists($fetcher_name)) {
        $fetcher = new $fetcher_name;
      }
    }

    if (!empty($fetcher)) {
      $this->data = $fetcher->fetchData();
    }

    $this->parse();
  }

  /**
   * Parse the data from the fetcher.
   */
  abstract protected function parse();

}
