<?php

namespace Dorgflow;

class Analyser {


  public function doStuff() {
    /*
    fetch git branch list
    fetch git log
    fetch d.org REST data

    parse each one

    create:
    - main work branch
    - feature branch
    - patch waypoint * N

    return them in a collection

    */

    /*
    $current_project = $this->fetchData('CurrentProject');
    dump($current_project);
    $branch_list = $this->fetchData('GitBranchList');
    dump($branch_list);
    $git_log = [];
    $issue_data = [];

    // Parsers!!

    // DEV: dummy data until I figure out how to do the fetch and parse...


    $branch_list = [
      '8.x-4.x',
    ];
    $current_branch = '8.x-4.x';
    $current_project = 'flag';
    $git_log = [];
    $issue_data = [];
    */

    $situation = new Situation();

    // Create waypoints.
    $master_branch = new Waypoint\MasterBranch($situation);
    $feature_branch = new Waypoint\FeatureBranch($situation);

    $feature_branch_log = $situation->getFeatureBranchLog();
    dump($feature_branch_log);
    $parent = $feature_branch;
    //$patch = Waypoint\Patch::getNextPatch($situation, $parent);

    // temp! issue data!
    $title = $situation->DrupalOrgIssueNode->getIssueNodeTitle();
    dump($title);

    /*
    work over the files from the issue
    create a Patch object for each one
    try to find a commit for each one



    */

    /*
    while ($patch = Waypoint\Patch::getNextPatch($patches)) {
      dump($patch);
      $patches[] = $patch;
    }
    */


    /*
    look at the

    */



    /*
    $waypoint_classes = [
      'MasterBranch',
    ];


    $waypoints = [];
    foreach ($waypoint_classes as $waypoint_class) {
      $full_waypoint_class = 'Dorgflow\\Waypoint\\' . $waypoint_class;

      do {
        $waypoint = $full_waypoint_class::create($current_project, $branch_list, $git_log, $issue_data);

        if (!empty($waypoint)) {
          $waypoints[] = $waypoint;
        }
      }
      while (!empty($waypoint));
    }

    dump($waypoints);
    */
  }

  protected function fetchData($class_name) {
    $fetcher_full_class_name = 'Dorgflow\\Fetcher\\' . $class_name;
    $fetcher = new $fetcher_full_class_name();
    $raw_data = $fetcher->fetchData();

    $parser_full_class_name = 'Dorgflow\\Parser\\' . $class_name;
    if (class_exists($parser_full_class_name)) {
      //$parser_full_class_name->
    }
  }


}
