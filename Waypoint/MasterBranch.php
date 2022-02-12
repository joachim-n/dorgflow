<?php

namespace Dorgflow\Waypoint;

class MasterBranch {

  /**
   * Regex for semver branch name.
   */
  const SEMVER_BRANCH_NAME_REGEX = '@\d.\d+.x@';

  /**
   * Regex patterns for a master branch.
   */
  const BRANCH_NAME_PATTERNS = [
    // Old style contrib, e.g. '8.x-1.x'.
    '\d.x-\d+-x',
    // Old style core.
    '\d.x',
    // Semver core/contrib.
    '\d.\d+.x',
  ];

  protected $branchName;

  protected $isCurrentBranch;

  function __construct(\Dorgflow\Service\GitInfo $git_info, \Dorgflow\Service\GitExecutor $git_executor) {
    $this->git_info = $git_info;
    $this->git_executor = $git_executor;

    // We require the master branch to be reachable.
    $branch_list = $this->git_info->getBranchListReachable();

    // Sort the branches by version number.
    uksort($branch_list, function ($a, $b) {
      // A semver branch is always taken to be newer than a non-semver. We
      // can't use version_compare() for this, as in contrib, something like
      // 2.0.x is newer than 8.x-1.x.
      if (preg_match(self::SEMVER_BRANCH_NAME_REGEX, $a) && !preg_match(self::SEMVER_BRANCH_NAME_REGEX, $b)) {
        // Second is lower than first.
        return 1;
      }
      if (!preg_match(self::SEMVER_BRANCH_NAME_REGEX, $a) && preg_match(self::SEMVER_BRANCH_NAME_REGEX, $b)) {
        // First is lower than second.
        return -1;
      }

      return version_compare($a, $b);
    });

    // Reverse so we have highest branch first.
    $branch_list = array_reverse($branch_list);

    $master_branch_regex = "@(" . implode('|', self::BRANCH_NAME_PATTERNS) . ')@';

    foreach ($branch_list as $branch => $sha) {
      // Identify the main development branch, of one of the following forms:
      //  - '7.x-1.x'
      //  - '7.x'
      //  - '8.0.x'
      if (preg_match($master_branch_regex, $branch)) {
        $this->branchName = trim($branch);

        $found = TRUE;

        break;
      }
    }

    if (empty($found)) {
      // This should trigger a complete failure -- throw an exception!
      throw new \Exception("Can't find a master branch.");
    }

    $this->isCurrentBranch = ($this->git_info->getCurrentBranch() == $this->branchName);
  }

  public function getBranchName() {
    return $this->branchName;
  }

  public function isCurrentBranch() {
    return $this->isCurrentBranch;
  }

  public function checkOutFiles() {
    $original_branch = $this->git_info->getCurrentBranch();

    if ($original_branch == $this->branchName) {
      throw new \Exception("On master branch {$original_branch}.");
    }

    // Check out the master branch.
    $this->git_executor->checkOutBranch($this->branchName);

    // Make the original branch current, but without changing the files.
    // This will allow us to apply a patch on the master branch, while ready to
    // make the commit for the patch on the feature branch.
    // We have to go the long way round, because the simply checking out the
    // master branch files while remaining on the feature branch will not take
    // into account any new files that are added by a patch.
    $this->git_executor->moveToBranch($original_branch);
  }

  /**
   * Checks out the branch.
   */
  public function gitCheckout() {
    // No need to do anything if the branch is current.
    if ($this->isCurrentBranch()) {
      return;
    }

    $branch_name = $this->getBranchName();
    $this->git_executor->checkOutBranch($branch_name);
  }

}
