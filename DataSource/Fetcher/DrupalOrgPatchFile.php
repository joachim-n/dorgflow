<?php

namespace Dorgflow\DataSource\Fetcher;

use Dorgflow\Situation;

class DrupalOrgPatchFile implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetchData(Situation $situation, $parameters) {
    $url = $parameters['url'];

    $file = file_get_contents($url);

    return $file;
  }

}
