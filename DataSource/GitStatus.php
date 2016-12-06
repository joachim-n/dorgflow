<?php

namespace Dorgflow\DataSource;

/**
 * Determines whether the current git repository is clean.
 */
class GitStatus extends DataSourceBase {

  /**
   * Returns whether the current git repository is clean.
   *
   * @return bool
   *  TRUE if clean, FALSE if local files have changes.
   */
  public function gitIsClean() {
    return $this->is_clean;
  }

  /**
   * {@inheritdoc}
   */
  protected function parse() {
    $diff_files = shell_exec("git diff-files");

    $this->is_clean = (empty($diff_files));
  }

}
