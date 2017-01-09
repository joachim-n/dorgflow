<?php

namespace Dorgflow\Service;

/**
 * Retrieves data from drupal.org.
 */
class DrupalOrg {

  protected $node_data;

  function __construct($analyser) {
    $this->analyser = $analyser;
  }

  public function getIssueNodeTitle() {

  }

  public function getNextCommentIndex() {

  }

  public function getIssueFileFieldItems() {

  }

  public function getFileEntity() {
  }

  public function getPatchFile() {
  }

  protected function fetchIssueNode() {
    $issue_number = $situation->getIssueNumber();

    print "Fetching node $issue_number from drupal.org.\n";

    $response = file_get_contents("https://www.drupal.org/api-d7/node/{$issue_number}.json");
    $node = json_decode($response);

    // return $node;
  }

}
