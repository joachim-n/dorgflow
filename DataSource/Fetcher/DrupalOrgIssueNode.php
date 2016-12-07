<?php

namespace Dorgflow\DataSource\Fetcher;

use Dorgflow\Situation;

/**
 * Retrieves an issue node from drupal.org.
 */
class DrupalOrgIssueNode implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetchData(Situation $situation) {
    // Set the user-agent for the request to drupal.org's API, to be polite.
    // See https://www.drupal.org/api
    ini_set('user_agent', "dorgpatch - https://github.com/joachim-n/dorgpatch.");

    $issue_number = $situation->getIssueNumber();
    $response = file_get_contents("https://www.drupal.org/api-d7/node/{$issue_number}.json");
    $node = json_decode($response);

    return $node;
  }

}
