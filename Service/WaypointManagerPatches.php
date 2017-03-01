<?php

namespace Dorgflow\Service;

use Dorgflow\Waypoint\Patch;

/**
 * Creates objects that represent patch waypoints in the workflow.
 */
class WaypointManagerPatches {

  function __construct($commit_message, $drupal_org, $git_log, $git_executor, $waypoint_manager_branches) {
    $this->commit_message = $commit_message;
    $this->drupal_org = $drupal_org;
    $this->git_log = $git_log;
    $this->git_executor = $git_executor;
    $this->waypoint_manager_branches = $waypoint_manager_branches;
  }

  /**
   * Creates the Patch Waypoints for the issue.
   *
   * TODO: docs!
   */
  public function setUpPatches() {
    // Get the field items for the issue node's file field.
    $issue_file_field_items = $this->drupal_org->getIssueFileFieldItems();

    $feature_branch_log = $this->git_log->getFeatureBranchLog();
    //dump($feature_branch_log);

    $patch_waypoints = [];


    // We work over the file field items from the node:
    //  - see whether it already exists as a feature branch commit
    //    - Yes: create a Patch object that records this.
    //    - No: get the file entity so we can check the actual patch file URL.
    //      - If it's a patch file, create a Patch object.
    // Issue file items are returned in creation order, earliest first.
    while ($issue_file_field_items) {
      // Get the next file item.
      $file_field_item = array_shift($issue_file_field_items);
      //dump($file_field_item);

      // Skip a file that is set to not be displayed.
      if (!$file_field_item->display) {
        continue;
      }

      $fid = $file_field_item->file->id;

      // Get a copy of the feature branch log array that we can search in
      // destructively, leaving the original to mark our place.
      $feature_branch_log_to_search_in = $feature_branch_log;

      // Work through the feature branch commit list until we find a commit
      // that matches.
      while ($feature_branch_log_to_search_in) {
        // Get the next commit.
        $commit = array_shift($feature_branch_log_to_search_in);
        $commit_message_data = $this->commit_message->parseCommitMessage($commit['message']);

        // If we find a commit from the feature branch that matches this patch,
        // then create a Patch waypoint and move on to the next file.
        if (!empty($commit_message_data['fid']) && $commit_message_data['fid'] == $fid) {
          // Create a patch waypoint for this patch.
          $patch = $this->getPatch($file_field_item, $commit['sha']);
          $patch_waypoints[] = $patch;

          // Replace the original log with the search copy, as we've found a
          // commit that works, and we want to start from here the next time we
          // look for a patch in the log.
          $feature_branch_log = $feature_branch_log_to_search_in;

          // Done with this file item.
          continue 2;
        }
      }

      // We didn't find a commit, so now get the file entity to look at the
      // filename, to see if it's a patch file.
      $file_entity = $this->drupal_org->getFileEntity($fid);
      $file_url = $file_entity->url;

      // Skip a file that is not a patch.
      $patch_filename = pathinfo($file_url, PATHINFO_FILENAME);
      $patch_file_extension = pathinfo($file_url, PATHINFO_EXTENSION);
      if ($patch_file_extension != 'patch') {
        continue;
      }

      // Get a copy of the feature branch log array that we can search in
      // destructively, leaving the original to mark our place.
      $feature_branch_log_to_search_in = $feature_branch_log;

      // Work through the feature branch commit list to see whether this is a
      // patch we uploaded.
      while ($feature_branch_log_to_search_in) {
        // Get the next commit.
        $commit = array_shift($feature_branch_log_to_search_in);
        $commit_message_data = $this->commit_message->parseCommitMessage($commit['message']);

        // If we find a commit from the feature branch that matches this patch,
        // then create a Patch waypoint and move on to the next file.
        // We need to do more than check equality to compare two patch filenames
        // as drupal.org may rename files for security or uniqueness.
        if (!empty($commit_message_data['filename']) && $this->patchFilenamesAreEqual($commit_message_data['filename'], $patch_filename)) {
          // Create a patch waypoint for this patch.
          $patch = $this->getPatch($file_field_item, $commit['sha']);
          $patch_waypoints[] = $patch;

          // Replace the original log with the search copy, as we've found a
          // commit that works, and we want to start from here the next time we
          // look for a patch in the log.
          $feature_branch_log = $feature_branch_log_to_search_in;

          // Done with this file item.
          continue 2;
        }
      }

      // We've not found a commit.
      // Create a patch waypoint for this patch.
      $patch = $this->getPatch($file_field_item);
      $patch_waypoints[] = $patch;

      // TODO:
      // if $feature_branch_log still contains commits, that means that there
      // are local commits at the tip of the branch!
    }

    //dump($patch_waypoints);

    return $patch_waypoints;
  }

  /**
   * Determine whether two patch filenames count as equal.
   *
   * This is necessary because drupal.org can change the name of an uploaded
   * file in two ways:
   *  - the filename is altered by file_munge_filename() for security reasons
   *  - an numeric suffix is added to prevent a filename collision.
   *
   * @param $local_filename
   *  The local filename.
   * @param $drupal_org_filename
   *  The name of the file from drupal.org.
   *
   * @return bool
   *  TRUE if the filenames are considered equal, FALSE if not.
   */
  protected function patchFilenamesAreEqual($local_filename, $drupal_org_filename) {
    // Quick positive.
    if ($local_filename == $drupal_org_filename) {
      return TRUE;
    }

    // Redo the work of file_munge_filename() on the local filename.
    // The extensions whitelist is that of the files field instance,
    // node-project_issue-field_issue_files in file
    // features/drupalorg_issues/drupalorg_issues.features.field_instance.inc
    // of the 'drupalorg' project repository.
    $extensions = 'jpg jpeg gif png txt xls pdf ppt pps odt ods odp gz tgz patch diff zip test info po pot psd yml mov mp4 avi mkv';
    // Remove any null bytes. See http://php.net/manual/security.filesystem.nullbytes.php
    $local_filename = str_replace(chr(0), '', $local_filename);

    $whitelist = array_unique(explode(' ', strtolower(trim($extensions))));

    // Split the filename up by periods. The first part becomes the basename
    // the last part the final extension.
    $filename_parts = explode('.', $local_filename);
    $new_filename = array_shift($filename_parts); // Remove file basename.
    $final_extension = array_pop($filename_parts); // Remove final extension.

    // Loop through the middle parts of the name and add an underscore to the
    // end of each section that could be a file extension but isn't in the list
    // of allowed extensions.
    foreach ($filename_parts as $filename_part) {
      $new_filename .= '.' . $filename_part;
      if (!in_array(strtolower($filename_part), $whitelist) && preg_match("/^[a-zA-Z]{2,5}\d?$/", $filename_part)) {
        $new_filename .= '_';
      }
    }
    $local_filename = $new_filename . '.' . $final_extension;

    // Check again.
    if ($local_filename == $drupal_org_filename) {
      return TRUE;
    }

    // Allow for a FILE_EXISTS_RENAME suffix on the drupal.org filename.
    $drupal_org_filename = preg_replace('@_\d+(?=\..+)@', '', $drupal_org_filename);

    // Final check.
    return ($local_filename == $drupal_org_filename);
  }

  /**
   * Finds the most recent commit on the feature branch that is for a patch.
   *
   * @return \Dorgflow\Waypoint\Patch
   *  The patch object, or NULL of none was found.
   */
  public function getMostRecentPatch() {
    $branch_log = $this->git_log->getFeatureBranchLog();
    // Reverse this so we get the most recent first.
    foreach (array_reverse($branch_log) as $sha => $commit) {
      $commit_data = $this->commit_message->parseCommitMessage($commit['message']);

      if (!empty($commit_data)) {
        // This is the most recent commit that has detectable commit data;
        // therefore the most recent that has a patch.
        // Create a patch object for this commit.
        $patch = $this->getPatch(NULL, $sha);
        return $patch;
      }
    }
  }

  /**
   * Creates a patch object.
   *
   * This takes care of injecting the services.
   *
   * @param $file_field_item = NULL
   *  The file field item from the issue node for the patch file, if there is one.
   * @param $sha = NULL
   *  The SHA for the patch's commit, if there is a commit.
   *
   * @return
   *  The new patch object.
   */
  public function getPatch($file_field_item = NULL, $sha = NULL) {
    $patch = new Patch(
      $this->drupal_org,
      $this->waypoint_manager_branches,
      $this->git_executor,
      $this->commit_message,
      $file_field_item,
      $sha
    );

    return $patch;
  }

}
