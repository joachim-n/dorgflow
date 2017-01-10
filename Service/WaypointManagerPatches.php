<?php

namespace Dorgflow\Service;

use Dorgflow\Waypoint\Patch;

/**
 * Creates objects that represent patch waypoints in the workflow.
 */
class WaypointManagerPatches {

  // TODO: add $waypoint_manager_branches
  function __construct($git_info, $git_log, $drupal_org, $git_executor) {
    $this->git_info = $git_info;
    $this->git_log = $git_log;
    $this->drupal_org = $drupal_org;
    $this->git_executor = $git_executor;
  }

  /**
   * Creates the Patch Waypoints for the issue.
   *
   * TODO: docs!
   */
  public function setUpPatches() {
    // Get the field items for the issue node's file field.
    $issue_file_field_items = $this->DrupalOrgIssueNode()->getIssueFileFieldItems();

    //dump($issue_file_field_items);
    //var_export($issue_file_field_items);
    /*
    $issue_file_field_items =
    array (
      0 =>
      (object) (array(
         'file' =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/file/5755031',
           'id' => '5755031',
           'resource' => 'file',
        )),
         'display' => '0',
      )),
      1 =>
      (object) (array(
         'file' =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/file/5755137',
           'id' => '5755137',
           'resource' => 'file',
        )),
         'display' => '0',
      )),
      2 =>
      (object) (array(
         'file' =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/file/5755185',
           'id' => '5755185',
           'resource' => 'file',
        )),
         'display' => '1',
      )),
      3 =>
      (object) (array(
         'file' =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/file/5755421',
           'id' => '5755421',
           'resource' => 'file',
        )),
         'display' => '1',
      )),
    );
    */

    $feature_branch_log = $this->GitFeatureBranchLog()->getFeatureBranchLog();
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
        $commit_message_data = $this->parseCommitMessage($commit['message']);

        // If we find a commit from the feature branch that matches this patch,
        // then create a Patch waypoint and move on to the next file.
        if (!empty($commit_message_data['fid']) && $commit_message_data['fid'] == $fid) {
          // Create a patch waypoint for this patch.
          $patch = new \Dorgflow\Waypoint\Patch($this, $this->git, $file_field_item, $commit['sha']);
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
      $file_entity = $this->DrupalOrgFileEntity(['fid' => $fid])->getFileEntity();
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
        $commit_message_data = $this->parseCommitMessage($commit['message']);

        // If we find a commit from the feature branch that matches this patch,
        // then create a Patch waypoint and move on to the next file.
        if (!empty($commit_message_data['filename']) && $commit_message_data['filename'] == $patch_filename) {
          // Create a patch waypoint for this patch.
          $patch = new \Dorgflow\Waypoint\Patch($this, $this->git, $file_field_item, $commit['sha']);
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
      $patch = new \Dorgflow\Waypoint\Patch($this, $this->git, $file_field_item);
      $patch_waypoints[] = $patch;

      // TODO:
      // if $feature_branch_log still contains commits, that means that there
      // are local commits at the tip of the branch!
    }

    //dump($patch_waypoints);

    return $patch_waypoints;
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
      $commit_data = $this->situation->parseCommitMessage($commit['message']);

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
      $file_field_item,
      $sha
    );
  }

}
