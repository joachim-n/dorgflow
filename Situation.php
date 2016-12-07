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
    $current_branch = $this->GitBranchList->getCurrentBranch();

    // TODO: analysis should be in Fetcher/Parser classes!
    $matches = [];
    preg_match("@^(?P<number>\d+)-@", $current_branch, $matches);
    if (!empty($matches['number'])) {
      return $matches['number'];
    }

    $issue_number = $this->UserInput->getIssueNumber();

    if (!empty($issue_number)) {
      return $issue_number;
    }

    // Dev mode.
    if ($this->devel_mode) {
      return 2801423;
    }

    throw new \Exception("Unable to find an issue number from command line parameter or current git branch.");
  }

  public function setUpMasterBranch() {
    $this->masterBranch = new \Dorgflow\Waypoint\MasterBranch($this);

    return $this->masterBranch;
  }

  public function setUpFeatureBranch() {
    $feature_branch = new Waypoint\FeatureBranch($this);

    return $feature_branch;
  }

  public function setUpPatches() {
    $patches = [];

    do {
      $patch = new Waypoint\Patch($this);

      if ($patch->cancel) {
        if ($patch->status == 'skip') {
          continue;
        }

        break;
      }

      $patches[] = $patch;

    } while (TRUE);

    return $patches;
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
      $data_source->setFetcher();
      $data_source->fetchData();
      $fetchers[$name][$key] = $data_source;
    }

    return $fetchers[$name][$key];
  }




}
