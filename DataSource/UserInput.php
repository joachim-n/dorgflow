<?php

namespace Dorgflow\DataSource;

/**
 * Extracts input data from user input.
 */
class UserInput extends DataSourceBase {

  protected $issueNumber;

  public function getIssueNumber() {
    return $this->issueNumber;
  }

  /**
   * {@inheritdoc}
   */
  protected function parse() {
    global $argv;
    if (!empty($argv[1])) {
      if (is_numeric($argv[1])) {
        $this->issueNumber = $argv[1];
      }
      else {
        // If the param is a URL, get the node ID from the end of it.
        // TODO! allow a #anchor at the end of the url for #new links.
        $matches = [];
        if (preg_match("@www\.drupal\.org/node/(?P<number>\d+)$@", $argv[1], $matches)) {
          $this->issueNumber = $matches['number'];
        }
      }
    }
  }

}
