<?php

namespace Dorgflow\DataSource;

/**
 * Gets the name of the current project.
 */
class CurrentProject extends DataSourceBase {

  public function getCurrentProjectName() {
    return $this->current_module;
  }

  protected function parse() {
    $working_dir = $this->data;

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
