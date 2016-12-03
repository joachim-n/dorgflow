<?php

namespace Dorgflow\Fetcher;

class CurrentProject {

  protected $fetched;

  public function getCurrentProjectName() {
    if (empty($this->fetched)) {
      $this->fetchData();
    }

    return $this->current_module;
  }

  public function fetchData() {
    // * Get the name of the current module, based on the working directory.
    // TODO: drush-specific!!!
    $working_dir = getcwd(); // drush_cwd();

    // Special case for core; I for one have Drupal installed in lots of
    // funnily-named folders.
    // Drupal 8.
    if (file_exists($working_dir . "/core/index.php")) {
      return 'drupal';
    }
    // Drupal 7 and prior.
    if (file_exists($working_dir . "/index.php")) {
      return 'drupal';
    }

    // Get the module name.
    $current_module = basename($working_dir);

    // Allow for module folders to have a suffix. (E.g., I might have views-6
    // and views-7 in my sandbox folder.)
    $current_module = preg_replace("@-.*$@", '', $current_module);

    $this->current_module = $current_module;
  }

}
