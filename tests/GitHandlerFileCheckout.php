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
    exec("git add main.txt");
    exec("git commit -m 'Initial commit.'");

    $b = shell_exec("git symbolic-ref --short -q HEAD");
    dump($b);
  }

  public function testFileCheckout() {
    dump('hi');

    $git = new \Dorgflow\Executor\Git;

    $mock_situation = $this->createMock(\Dorgflow\Situation::class);

    $patch_b_text = file_get_contents('patch-b.patch');
    $patch_b = $this->getMockBuilder(\Dorgflow\Waypoint\Patch::class)
      //->setConstructorArgs([$mock_situation])
      ->disableOriginalConstructor()
      ->setMethods(['getPatchFile'])
      ->getMock();
    $patch_b->method('getPatchFile')
      ->willReturn($patch_b_text);

    //$git->checkOutFiles('master');
    $git->checkOutFilesPorcelain('master');

    dump('apply patch.......');
    $applied = $patch_b->applyPatchFile();
    dump($applied);

    if (!$applied) {
      $this->fail();
    }
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
