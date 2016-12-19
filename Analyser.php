<?php

namespace Dorgflow;

// NOT CURRENTLY IN USE.
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

    $issue_files = $situation->DrupalOrgIssueNode->getIssueFiles();
    //dump($issue_files);

    $patches = [];

    do {
      $patch = new Waypoint\Patch($situation);

      if ($patch->cancel) {
        break;
      }

      $patches[] = $patch;

    } while (TRUE);

    dump($patches);
    
    $last_patch = end($patches);
    dump($last_patch);
    //$file = $last_patch->getPatchFile();
    //dump($file);
    
    // Now ready for setup command to create branch and make commits!
    
    
    //foreach ($issue_files as $file) {
    //}


    /*
    work over the files from the issue
    create a Patch object for each one
    try to find a commit for each one



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


}
