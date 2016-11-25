<?php

namespace Dorgflow\Fetcher;

/**
 * Fetches the contents for a file entity.
 */
class DrupalOrgPatchFile {

  public function getPatchFile($fid) {
    // Get the file entity first.
    ini_set('user_agent', "dorgpatch - https://github.com/joachim-n/dorgpatch.");
    $response = file_get_contents("https://www.drupal.org/api-d7/file/{$fid}.json");
    $response = json_decode($response);
    $url = $response->url;
    $file = file_get_contents($url);

    return $file;
  }

}
