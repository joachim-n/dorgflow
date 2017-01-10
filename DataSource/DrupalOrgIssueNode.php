<?php

namespace Dorgflow\DataSource;

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
   * Gets the field items for the issue files field, in order of creation.
   *
   * Note that:
   *  - it's up to the caller to filter out files that aren't set to be
   *    displayed.
   *  - it's not possible with this data to filter out non-patch files; the full
   *    file entity must be retrieved for this.
   */
  public function getIssueFileFieldItems() {
    $files = $this->data->field_issue_files;

    // Ensure these are in creation order by ordering them by fid.
    // TODO: in due course, get the comment index data!!! -- see d.org issue!
    usort($files, function($a, $b) {
      return ($a->file->id <=> $b->file->id);
    });

    return $files;
  }

  // Todo:
  // most recent file

}
