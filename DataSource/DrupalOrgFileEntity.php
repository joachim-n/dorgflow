<?php

namespace Dorgflow\DataSource;

/**
 * Gets a file entity from drupal.org.
 */
class DrupalOrgFileEntity extends DataSourceBase {

  public function getFileEntity() {
    return $this->data;
  }

}
