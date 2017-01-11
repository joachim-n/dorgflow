<?php

namespace Dorgflow\Tests;

/**
 * Tests the application of a sequence of patches.
 *
 * Run with:
 * @code
 *   vendor/bin/phpunit  tests/GitHandlerFileCheckout.php
 * @endcode
 */
class GitHandlerFileCheckout extends \PHPUnit_Framework_TestCase {

  /**
   * Set up a git repository for the folder Testing/repository.
   */
  protected function setUp() {
    chdir('Testing/repository');
    exec("git init .");

    // Make a first commit with the test file.
    exec("git add main.txt");
    exec("git commit -m 'Initial commit.'");

    $this->test_file_original_contents = file_get_contents('main.txt');
  }

  public function testFileCheckout() {
    $git = new \Dorgflow\Service\GitExecutor;

    $initial_sha = shell_exec("git rev-parse HEAD");

    // Apply a sequence of patches.
    $patch_filenames = [
      'patch-b.patch',
      'patch-c.patch',
    ];
    foreach ($patch_filenames as $patch_filename) {
      // Put the files back to the initial commit so that the patch applies.
      $git->checkOutFiles($initial_sha);

      $patch_text = file_get_contents($patch_filename);
      $applied = $git->applyPatch($patch_text);

      if (!$applied) {
        $this->fail("Patch $patch_filename failed to apply");
      }

      // Make the commit.
      // TODO: use Git handler when this gains the ability.
      shell_exec("git commit  --allow-empty --message='Commit for $patch_filename.'");
    }

    $log = shell_exec("git rev-list master --pretty=oneline");
    $log_lines = explode("\n", trim($log));
    $this->assertEquals(3, count($log_lines), 'The git log has the expected number of commits.');
  }

  /**
   * Remove the testing git repository created in setUp().
   */
  protected function tearDown() {
    // Restore the file we've changed.
    file_put_contents('main.txt', $this->test_file_original_contents);

    // Remove the git repo. Be very careful it's the right one!
    $current_dir = getcwd();
    if (basename($current_dir) != 'repository') {
      throw new \Exception("Trying to delete wrong git repository!");
    }
    exec('rm -rf .git');
  }

}
