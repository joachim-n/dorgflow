<?php

namespace Dorgflow\DataSource\Fetcher;

use Dorgflow\Situation;

/**
 * Retrieves an issue node from drupal.org.
 */
class DrupalOrgIssueNode extends DrupalOrgFetcher implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetchData(Situation $situation, $parameters) {
    $issue_number = $situation->getIssueNumber();

    print "Fetching node $issue_number from drupal.org.\n";

    $response = file_get_contents("https://www.drupal.org/api-d7/node/{$issue_number}.json");
    $node = json_decode($response);

    return $node;
  }

}
