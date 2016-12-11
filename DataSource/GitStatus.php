<?php

namespace Dorgflow\DataSource;

/**
 * Determines whether the current git repository is clean.
 */
class GitStatus extends DataSourceBase {

  protected $is_clean;

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
    $this->is_clean = (empty($this->data));
  }

}
