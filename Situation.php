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
    $current_branch = $this->GitBranchList()->getCurrentBranch();

    // TODO: analysis should be in Fetcher/Parser classes!
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

  // TODO: merge ---
  public function setUpMasterBranch() {
    $this->masterBranch = new \Dorgflow\Waypoint\MasterBranch($this);

    return $this->masterBranch;
  }

  // TODO: merge ---
  public function setUpFeatureBranch() {
    $this->feature_branch = new Waypoint\FeatureBranch($this);

    return $this->feature_branch;
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
    // New:

    //$issue_file_field_items = $this->DrupalOrgIssueNode()->getIssueFiles();
    //dump($issue_file_field_items);
    //var_export($issue_file_field_items);

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

    $feature_branch_log = $this->GitFeatureBranchLog()->getFeatureBranchLog();
    dump($feature_branch_log);

    $patch_waypoints = [];


    /*
    cream off next file field item from the node:
      does it exist as a commit in the feature branch?
      cream off feature branch commits until one matches.
        YES: record the patch waypoint with the file item; no need to go further
          as we won't be applying it
          THIS COULD BE A PATCH WE UPLOADED, and therefore a DIFFERENT STYLE OF
          commit message!
        NO: get the file entity so we can look at the URL
          Is it a patch?
          YES: record the patch waypoint with the patch file
          NO: skip
    */


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

      // Work through the feature branch commit list until we find a commit
      // that matches.
      while ($feature_branch_log) {
        // Get the next commit.
        // BAD: we lose the array key which is the SHA! TODO!
        $commit = array_shift($feature_branch_log);

        // Does it match? We only have the file ID to go on at this point.
        // TODO: more than one commit message format!!! -- our OWN patches
        // should have EMPTY commits made
        $commit_message_data = \Dorgflow\Waypoint\Patch::parseCommitMessage($commit['message']);
        if (!empty($commit_message_data) && $commit_message_data['fid'] == $fid) {

          // TODO! set up the patch
          // add it to patch array
          $patch = new \Dorgflow\Waypoint\Patch($this, $file_field_item, $commit['sha']);
          $patch_waypoints[] = $patch;

          // Done with this file item.
          continue 2;
        }
      }

      // We didn't find a commit, so now get the file entity to see if it's a
      // patch file
      $file_entity = $this->DrupalOrgFileEntity(['fid' => $fid])->getFileEntity();
      $file_url = $file_entity->url;

      // Skip a file that is not a patch.
      if (pathinfo($file_url, PATHINFO_EXTENSION) != 'patch') {
        continue;
      }

      $patch = new \Dorgflow\Waypoint\Patch($this, $file_field_item);
      $patch_waypoints[] = $patch;
    }

    //dump($patch_waypoints);

    return $patch_waypoints;
  }

  public function getFeatureBranchLog() {
    // return array
    // SHA => commit message
    // Oldest first.


    // ARGH need MastBranch!!!
    $fetcher = $this->getFetcher('GitFeatureBranchLog');
    return $fetcher->getFeatureBranchLog($this->masterBranch);
  }

  /**
   *
   */
  protected function getFetcher($name) {
    // statically cache fetchers, as they statically cache their own data.
    if (!isset($fetchers[$name])) {

      $fetcher_full_class_name = 'Dorgflow\\Fetcher\\' . $name;
      $fetchers[$name] = new $fetcher_full_class_name();
    }

    return $fetchers[$name];
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
