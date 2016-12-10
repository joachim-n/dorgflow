<?php

namespace Dorgflow\DataSource\Fetcher;

use Dorgflow\Situation;

class GitFeatureBranchLog implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetchData(Situation $situation, $parameters) {
    $master_branch_name = $situation->getMasterBranch()->getBranchName();
    $feature_branch_name = $situation->getFeatureBranch()->getBranchName();

    $git_log = shell_exec("git rev-list {$feature_branch_name} ^{$master_branch_name} --pretty=oneline --reverse");

    return $git_log;
  }

}
