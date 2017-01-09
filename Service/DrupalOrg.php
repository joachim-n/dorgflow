<?php

namespace Dorgflow\Service;

/**
 * Retrieves data from drupal.org's REST API.
 */
class DrupalOrg {

  protected $node_data;

  protected $file_entities;

  function __construct($analyser) {
    $this->analyser = $analyser;

    // Set the user-agent for the request to drupal.org's API, to be polite.
    // See https://www.drupal.org/api
    ini_set('user_agent', "Dorgflow - https://github.com/joachim-n/dorgflow.");
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

  /**
   * Fetches a file entity from drupal.org's REST API.
   *
   * @param $fid
   *  The file entity ID.
   *
   * @return
   *  The file entity data.
   */
  public function getFileEntity($fid) {
    if (!isset($file_entities[$fid])) {
      $response = file_get_contents("https://www.drupal.org/api-d7/file/{$fid}.json");
      $file_entities[$fid] = json_decode($response);
    }

    return $file_entities[$fid];
  }

  /**
   * Fetches a patch file from drupal.org.
   *
   * @param $url
   *  The patch file URL.
   *
   * @return
   *  The patch file contents.
   */
  public function getPatchFile($url) {
    // @todo: this probably doesn't need any caching, but check!

    $file = file_get_contents($url);
    return $file;
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
