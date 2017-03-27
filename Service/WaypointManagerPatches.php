<?php

namespace Dorgflow\Service;

use Dorgflow\Waypoint\Patch;
use Dorgflow\Waypoint\LocalPatch;

/**
 * Creates objects that represent patch waypoints in the workflow.
 */
class WaypointManagerPatches {

  protected $patches = [];

  function __construct($commit_message, $drupal_org, $git_log, $git_executor, $analyser, $waypoint_manager_branches) {
    $this->commit_message = $commit_message;
    $this->drupal_org = $drupal_org;
    $this->git_log = $git_log;
    $this->git_executor = $git_executor;
    $this->analyser = $analyser;
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
          $patch = $this->getWaypoint(Patch::class, $file_field_item, $commit['sha'], $commit_message_data);
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
          $patch = $this->getWaypoint(Patch::class, $file_field_item, $commit['sha'], $commit_message_data);
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
      $patch = $this->getWaypoint(Patch::class, $file_field_item);
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
   * Creates a Waypoint object representing a patch about to be created.
   *
   * @return \Dorgflow\Waypoint\LocalPatch
   *  A LocalPatch waypoint object.
   */
  public function getLocalPatch() {
    // TODO: sanity checks.
    // if tip commit is a d.org patch, then bail. pointless
    // if tip commit is a local patch, then bail. pointless

    return $this->getWaypoint(LocalPatch::class);
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
      $commit_message_data = $this->commit_message->parseCommitMessage($commit['message']);

      if (empty($commit_message_data)) {
        // Skip a commit that isn't a patch.
        continue;
      }

      // If the patch is local, we might not want to diff against it, in the
      // case that it's a prior run at making the patch we're making now.
      // (The use case is the user makes the patch, fixes a typo, makes the
      // patch again.)
      // Compare the comment index of this patch with the expected comment index
      // of the next patch.
      // (Note that local patch commits written prior to version 1.1.3 won't
      // have the comment index. In this case, we play safe (and keep the old
      // buggy behaviour) and return this commit.
      if (!empty($commit_message_data['local']) && isset($commit_message_data['comment_index'])) {
        $next_comment_number = $this->drupal_org->getNextCommentIndex();

        // If the comment numbers are the same, then skip this patch.
        if ($commit_message_data['comment_index'] == $next_comment_number) {
          continue;
        }

        // If the comment index is different, then there are two possibilities:
        // - it's an older patch that the user previously uploaded, and we are
        //   right to return this.
        // - it is in fact a prior version of the patch we're making now, but
        //   in the time since it was made, another drupal.org user posted a
        //   comment to the node, causing getNextCommentIndex() to now return
        //   a higher number. There is no simple way we can deal with this
        //   scenario, short of checking for patches on any comments since
        //   the commit's comment index, and then checking these patches to
        //   see whether they match with the diff that the commit represents,
        //   which is all a lot of work for an edge case.
        //   So for now, we do return this, even though it could be incorrect.
      }

      // If we have commit data, then this is the most recent commit that is a
      // patch.
      // Create a patch object for this commit and we're done.
      $patch = $this->getWaypoint(Patch::class, NULL, $sha, $commit_message_data);
      return $patch;
    }
  }

  /**
   * Creates a waypoint object of the given class
   *
   * This takes care of injecting the services.
   *
   * @param string $class_name
   *  The fully-qualified name of the class to instantiate.
   * @param array $params
   *  (optional) Further parameters to pass to the constructor after the
   *  injected services.
   *
   * @return
   *  The new object.
   */
  protected function getWaypoint($class_name, ...$params) {
    $waypoint = new $class_name(
      $this->drupal_org,
      $this->waypoint_manager_branches,
      $this->git_executor,
      $this->commit_message,
      $this->analyser,
      // Splat operator! :)
      ...$params
    );

    return $waypoint;
  }

}
