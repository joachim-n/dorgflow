<?php

namespace Dorgflow\DataSource\Fetcher;

use Dorgflow\Situation;

/**
 * Fetches a file entity from drupal.org.
 */
class DrupalOrgFileEntity implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetchData(Situation $situation, $parameters) {
    $fid = $parameters['fid'];
    
    ini_set('user_agent', "dorgpatch - https://github.com/joachim-n/dorgpatch.");
    $response = file_get_contents("https://www.drupal.org/api-d7/file/{$fid}.json");
    $file_entity = json_decode($response);

    return $file_entity;
  }

}
