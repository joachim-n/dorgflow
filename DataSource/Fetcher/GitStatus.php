<?php

namespace Dorgflow\DataSource\Fetcher;

use Dorgflow\Situation;

class GitStatus implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetchData(Situation $situation, $parameters) {
    $diff_files = shell_exec("git diff-files");

    return $diff_files;
  }

}
