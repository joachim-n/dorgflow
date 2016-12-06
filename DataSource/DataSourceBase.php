<?php

namespace Dorgflow\DataSource;

use Dorgflow\Situation;
use Dorgflow\DataSource\Fetcher\FetcherInterface;

/**
 * Each data source class provides one piece of data, such as the list of git
 * branches, or the drupal.org issue node.
 */
abstract class DataSourceBase {

  protected $data;

  /**
   * Constructor.
   *
   * Sets up the fetcher object of the same class name as this, if the class
   * exists. May be overridden with a parameter, for testing purposes.
   */
  public function __construct(Situation $situation, FetcherInterface $fetcher = NULL) {
    if (empty($fetcher)) {
      $reflect = new \ReflectionClass($this);
      $short_name = $reflect->getShortName();
      $fetcher_name = "Dorgflow\\DataSource\\Fetcher\\$short_name";
      if (class_exists($fetcher_name)) {
        $fetcher = new $fetcher_name;
      }
    }

    if (!empty($fetcher)) {
      $this->data = $fetcher->fetchData($situation);
    }

    $this->parse();
  }

  /**
   * Perform one-time parsing of the data from the fetcher.
   */
  protected function parse() {
    // Some data sources don't need this.
  }

}
