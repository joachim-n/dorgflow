<?php

/**
 * @file
 * Contains GitWorkBranchTest.
 */

namespace Dorgflow;

/**
 * Tests the GitBranchList class.
 *
 * Run with:
 * @code
 *   vendor/bin/phpunit  tests/GitWorkBranchTest.php
 * @endcode
 */
class GitWorkBranchTest extends \PHPUnit_Framework_TestCase {

  /**
   * Test.
   */
  public function testGitWorkBranch() {
    $work_branch_parser = new \Dorgflow\Parser\GitBranchList();

    $git_output = <<<EOT
  8.3.x
* 12345-foo-bar
EOT;


    $work_branch_parser->setInput($git_output);
    $work_branch_parser->parse();


    $this->assertEquals('12345-foo-bar', $work_branch_parser->get('currentBranch'), 'The current branch was found.');
  }

}
