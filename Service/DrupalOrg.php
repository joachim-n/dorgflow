<?php

namespace Dorgflow\Service;

/**
 * Retrieves data from drupal.org's REST API.
 */
class DrupalOrg {

  protected $node_data;

  function __construct($analyser) {
    $this->analyser = $analyser;
  }

  /**
   * Returns the issue node title.
   *
   * @return string
   *  The issue node title.
   */
  public function getIssueNodeTitle() {
    if (!isset($this->node_data)) {
      $this->fetchIssueNode();
    }

    return $this->node_data->title;
  }

  /**
   * Returns the expected index for the next comment.
   *
   * @return int
   *  The comment index for the next comment (assuming nobody else posts a
   *  comment in the meantime!).
   */
  public function getNextCommentIndex() {
    if (!isset($this->node_data)) {
      $this->fetchIssueNode();
    }

    $comment_count = $this->node_data->comment_count;
    $next_comment_index = $comment_count + 1;
    return $next_comment_index;
  }

  /**
   * Gets the field items for the issue files field, in order of creation.
   *
   * @return
   *  An array of Drupal file field items.
   */
  public function getIssueFileFieldItems() {
    if (!isset($this->node_data)) {
      $this->fetchIssueNode();
    }

    $files = $this->node_data->field_issue_files;

    // Ensure these are in creation order by ordering them by fid.
    // TODO: in due course, get the comment index data!!! -- see d.org issue!
    usort($files, function($a, $b) {
      return ($a->file->id <=> $b->file->id);
    });

    return $files;
  }

  public function getFileEntity() {
  }

  public function getPatchFile() {
  }

  /**
   * Fetches the issue node from drupal.org's REST API.
   */
  protected function fetchIssueNode() {
    $issue_number = $this->analyser->deduceIssueNumber();

    print "Fetching node $issue_number from drupal.org.\n";

    $response = file_get_contents("https://www.drupal.org/api-d7/node/{$issue_number}.json");

    $this->node_data = json_decode($response);
  }

}
