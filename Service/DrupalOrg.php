<?php

namespace Dorgflow\Service;

/**
 * Retrieves data from drupal.org's REST API.
 */
#[\AllowDynamicProperties]
class DrupalOrg {

  /**
   * The issue node data array from drupal.org.
   */
  protected $node_data;

  /**
   * An array of file entities from drupal.org, keyed by fid.
   */
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
   *  An array of Drupal file field items. This has the structure:
   *  - (delta): The key is the file field delta. Contains an array with these
   *    properties:
   *    - display: A boolean indicating whether the file is set to be displayed
   *      in the node output.
   *    - file: An object with these properties:
   *      - uri: The file URI.
   *      - id: The file entity ID.
   *      - resource: The type of the resource, in this case, 'file'.
   *      - cid: The comment entity ID of the comment at which this file was
   *        added.
   *    - index: The natural index of the comment this file was added with.
   */
  public function getIssueFileFieldItems(int $starting_comment_index = 0) {
    if (!isset($this->node_data)) {
      $this->fetchIssueNode();
    }

    $files = $this->node_data->field_issue_files;

    // Get the comment data from the node, so we can add the comment index
    // number to each file.
    // Create a lookup from comment ID to natural index.
    $comment_id_natural_indexes = [];
    foreach ($this->node_data->comments as $index => $comment_item) {
      // On d.org, the comments are output with a natural index starting from 1.
      $natural_index = $index + 1;

      $comment_id_natural_indexes[$comment_item->id] = $natural_index;
    }

    foreach ($files as $delta => &$file_item) {
      // A file won't have a comment ID if it was uploaded when the node was
      // created.
      // TODO: file a bug in the relevant Drupal module, following up my last
      // patch to add this data.
      if (isset($file_item->file->cid)) {
        $file_item_cid = $file_item->file->cid;
        $file_item->index = $comment_id_natural_indexes[$file_item_cid];
      }
      else {
        $file_item->index = 0;
      }

      if ($starting_comment_index && $file_item->index < $starting_comment_index) {
        unset($files[$delta]);
      }
    }

    // Ensure these are in creation order by ordering them by the comment index.
    uasort($files, function($a, $b) {
      return ($a->index <=> $b->index);
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

      if ($response === FALSE) {
        throw new \Exception("Failed getting file entity {$fid} from drupal.org.");
      }

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

    if ($file === FALSE) {
      throw new \Exception("Failed getting file {$url} from drupal.org.");
    }

    return $file;
  }

  /**
   * Fetches the issue node from drupal.org's REST API.
   */
  protected function fetchIssueNode() {
    $issue_number = $this->analyser->deduceIssueNumber();

    print "Fetching node $issue_number from drupal.org.\n";

    $response = file_get_contents("https://www.drupal.org/api-d7/node/{$issue_number}.json");

    if ($response === FALSE) {
      throw new \Exception("Failed getting node {$issue_number} from drupal.org.");
    }

    $this->node_data = json_decode($response);
  }

}
