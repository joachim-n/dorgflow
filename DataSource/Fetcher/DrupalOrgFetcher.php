<?php

namespace Dorgflow\DataSource\Fetcher;

use Dorgflow\Situation;

/**
 * Base class for fetchers that retrieve data from drupal.org.
 */
class DrupalOrgFetcher {

  public function __construct() {
    // Set the user-agent for the request to drupal.org's API, to be polite.
    // See https://www.drupal.org/api
    ini_set('user_agent', "Dorgflow - https://github.com/joachim-n/dorgflow.");
  }
  
}
