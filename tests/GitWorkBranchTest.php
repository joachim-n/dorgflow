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
    $branch_list = [
      '* 12345-foo-bar',
      '  67890',
    ];
    
    
    $work_branch_parser = new \Dorgflow\Parser\GitBranchList();
    
    $git_output = <<<EOT
    master
  * sandbox-oo
EOT;
    
    
    $work_branch_parser->setInput($git_output);
    $work_branch_parser->parse();
    
    return;

    $this->assertTrue($work_branch->exists(), 'A work branch was correctly found.');
    $this->assertEquals('12345-foo-bar', $work_branch->getBranchName(), 'The branch name was found.');
    $this->assertEquals('12345', $work_branch->getIssueNumber(), 'The issue number for the branch was found.');
    $this->assertEquals('foo-bar', $work_branch->getIssueDescription(), 'The issue description for the branch was found.');
  }

}
