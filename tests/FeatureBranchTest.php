<?php

namespace Dorgflow\Tests;

/**
 * Tests the FeatureBranch class.
 *
 * Run with:
 * @code
 *   vendor/bin/phpunit tests/FeatureBranchTest.php
 * @endcode
 */
class FeatureBranchTest extends \PHPUnit_Framework_TestCase {

  public function testFeatureBranch() {
    $git = $this->getMockBuilder(\Dorgflow\Executor\Git::class)
      ->disableOriginalConstructor()
      ->setMethods([])
      ->getMock();
    
    $situation = $this->getMockBuilder(\Dorgflow\Situation::class)
      ->setConstructorArgs([$git])
      ->setMethods([
        'getIssueNumber',
        'GitBranchList_getBranchList',
        'GitCurrentBranch_getCurrentBranch',
        'DrupalOrgIssueNode_getIssueNodeTitle',
      ])
      ->getMock();
      
    $situation->method('getIssueNumber')
      ->willReturn(123456);

    $situation->method('GitBranchList_getBranchList')
      ->willReturn([]);
      
    $situation->method('GitCurrentBranch_getCurrentBranch')
      ->willReturn('notthebranchyouseek');
      
    $situation->method('DrupalOrgIssueNode_getIssueNodeTitle')
      ->willReturn('the title of the issue');

    $feature_branch = $this->feature_branch = new \Dorgflow\Waypoint\FeatureBranch($situation);
    
    $exists = $feature_branch->exists();
    $this->assertFalse($exists);
    
    $branch_name = $feature_branch->getBranchName();
    $this->assertEquals($branch_name, '123456-the-title-of-the-issue');
  }

}
