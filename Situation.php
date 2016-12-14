<?php

namespace Dorgflow;

// Wrapper around fetchers and parsers.
// Is injected into Waypoints
class Situation {

  public $devel_mode = FALSE;

  // TODO: rename!
  protected $fetchers = [];

  protected $issue_number;

  // TODO: accessor!
  public $masterBranch;

  /**
   * Magic method: get a data fetcher.
   */
  public function __get($name) {
    // TODO: decomission!
    return $this->getDataSource($name, []);
  }

  /**
   * Magic method: get a data fetcher.
   */
  public function __call($name, $parameters) {
    return $this->getDataSource($name, $parameters);
  }

  /**
   * Get the issue number.
   *
   * @return int
   *  The issue number, which is the nid of the drupal.org issue node.
   */
  public function getIssueNumber() {
    /*
    // TODO: cache?
    if (isset($this->issue_number)) {
      return $this->issue_number;
    }
    */

    // Try to deduce an issue number from the current branch.
    // TODO, no try to get a feature branch instead.
    $current_branch = $this->GitBranchList()->getCurrentBranch();

    // TODO: analysis should be in DataSource classes!
    $matches = [];
    preg_match("@^(?P<number>\d+)-@", $current_branch, $matches);
    if (!empty($matches['number'])) {
      return $matches['number'];
    }

    $issue_number = $this->UserInput()->getIssueNumber();

    if (!empty($issue_number)) {
      return $issue_number;
    }

    // Dev mode.
    if ($this->devel_mode) {
      return 2801423;
    }

    throw new \Exception("Unable to find an issue number from command line parameter or current git branch.");
  }

  public function getMasterBranch() {
    if (empty($this->masterBranch)) {
      $this->masterBranch = new \Dorgflow\Waypoint\MasterBranch($this);
    }

    return $this->masterBranch;
  }

  public function getFeatureBranch() {
    if (empty($this->feature_branch)) {
      $this->feature_branch = new \Dorgflow\Waypoint\FeatureBranch($this);
    }

    return $this->feature_branch;
  }

  /**
   * Creates the Patch Waypoints for the issue.
   *
   * TODO: docs!
   */
  public function setUpPatches() {
    // Get the field items for the issue node's file field.
    $issue_file_field_items = $this->DrupalOrgIssueNode()->getIssueFiles();

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
    dump($feature_branch_log);

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
          $patch = new \Dorgflow\Waypoint\Patch($this, $file_field_item, $commit['sha']);
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
          $patch = new \Dorgflow\Waypoint\Patch($this, $file_field_item, $commit['sha']);
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
      $patch = new \Dorgflow\Waypoint\Patch($this, $file_field_item);
      $patch_waypoints[] = $patch;

      // TODO:
      // if $feature_branch_log still contains commits, that means that there
      // are local commits at the tip of the branch!
    }

    //dump($patch_waypoints);

    return $patch_waypoints;
  }

  protected function parseCommitMessage($message) {
    // TODO: move this to the same sort of place as the creation as these
    // messages!
    $pattern_remote = "Patch from Drupal.org. File: (?P<filename>.+\.patch); fid (?P<fid>\d+). Automatic commit by dorgflow.";
    $patern_local   = "Patch for Drupal.org. File: (?P<filename>.+\.patch). Automatic commit by dorgflow.";
    $matches = [];
    preg_match("@^($pattern_remote|$patern_local)@", $message, $matches);
    if (!empty($matches)) {
      $return = [
        'filename' => $matches['filename'],
      ];
      if (isset($matches['fid'])) {
        $return['fid'] = $matches['fid'];
      }
      // TODO: 'comment_index'
    }
    else {
      $return = FALSE;
    }

    return $return;
  }


  /**
   * Returns a new or cached data source object.
   *
   * @param $name
   *  The class name of the data source object. This is a class in the
   *  Dorgflow\DataSource namespace.
   */
  protected function getDataSource($name, $parameters) {
    // Expect a single param, which is itself an array of named parameters.
    if (!empty($parameters)) {
      $parameters = $parameters[0];
    }

    // Most data sources are singletons, and the parameters array will be empty.
    // Create a key for our static cache.
    if (empty($parameters)) {
      $key = '';
    }
    else {
      $key = serialize($parameters);
    }

    // Statically cache data sources, as they statically cache their own data.
    if (!isset($fetchers[$name][$key])) {
      $fetcher_full_class_name = 'Dorgflow\\DataSource\\' . $name;

      $data_source = new $fetcher_full_class_name($this);
      // TODO: not ideal; too much external driving of this class.
      $data_source->setParameters($parameters);
      if (isset($parameters['fetcher'])) {
        $data_source->setFetcher($parameters['fetcher']);
      }
      else {
        $data_source->setFetcher();
      }
      $data_source->fetchData();
      $fetchers[$name][$key] = $data_source;
    }

    return $fetchers[$name][$key];
  }




}
