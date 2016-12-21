<?php

namespace Dorgflow\Tests;

/**
 * Tests the TODO class.
 *
 * Run with:
 * @code
 *   vendor/bin/phpunit  tests/GitHandlerFileCheckout.php
 * @endcode
 */
class GitHandlerFileCheckout extends \PHPUnit_Framework_TestCase {

  protected function setUp() {
    chdir('Testing/repository');
    exec("git init .");
  }

  public function testFileCheckout() {
    dump('hi');
  }


  protected function tearDown() {
    // Remove the git repo. Be very careful it's the right one!
    $current_dir = getcwd();
    if (basename($current_dir) != 'repository') {
      throw new Exception();
    }
    exec('rm -rf .git');
  }

}
