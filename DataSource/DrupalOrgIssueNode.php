<?php

namespace Dorgflow\DataSource;

use EclipseGc\DrupalOrg\Api\DrupalClient;
use stdClass;

class DrupalOrgIssueNode extends DataSourceBase {

  public function getPatchList() {
    // Keyed by comment index number.
    return [
      // TODO!
    ];
  }

  public function getIssueNodeTitle() {
    return $this->data->title;
  }

  /**
   * Get the index number of the next comment to be posted to the issue.
   *
   * @return int
   *  The comment index number.
   */
  public function getNextCommentIndex() {
    $comment_count = $this->data->comment_count;
    $next_comment_index = $comment_count + 1;
    return $next_comment_index;
  }

  /**
   * Gets the files attached to the issue, in order of creation.
   */
  public function getIssueFiles() {
    $files = $this->data->field_issue_files;

    // TODO: filter out interdiffs!!!
    // ... which we can't know till we have the file entities???

    // TODO: filter out ones that are hidden?

    // Ensure these are in creation order by ordering them by fid.
    // TODO: in due course, get the comment index data!!! -- see d.org issue!
    usort($files, function($a, $b) {
      return ($a->file->id <=> $b->file->id);
    });

    return $files;
  }

  // generator function, yields patch files from the issue node one by one
  public function getNextIssueFile() {
    $files = $this->getIssueFiles();

    while ($files) {
      $next_file = array_shift($files);
      yield $next_file;
    }
  }

  // Todo:
  // most recent file

}
