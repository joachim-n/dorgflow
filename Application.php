<?php

namespace Dorgflow;

use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication {

  /**
   * {@inheritdoc}
   */
  public function find($name) {
    //dump($name);
    // TODO: handle non-name first command to be the setup command

    return parent::find($name);
  }

}
