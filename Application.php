<?php

namespace Dorgflow;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;

/**
 * Symfony application.
 */
class Application extends SymfonyApplication {

  /**
   * {@inheritdoc}
   */
  public function find(string $name): Command {
    // Handle the setup command, which is invoked with an issue number or URL.
    // This can't be registered as the default, as the patch command is.
    if (is_numeric($name)) {
      return $this->get('setup');
    }
    if (strpos($name, 'drupal.org') !== FALSE) {
      return $this->get('setup');
    }

    return parent::find($name);
  }

}
