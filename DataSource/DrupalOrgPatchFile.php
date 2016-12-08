<?php

namespace Dorgflow\DataSource;

/**
 * Fetches the contents for a file entity.
 */
class DrupalOrgPatchFile extends DataSourceBase {

  public function getPatchFile() {
    return $this->data;
  }

}
