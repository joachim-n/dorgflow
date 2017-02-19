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
class FeatureBranchTest extends \PHPUnit\Framework\TestCase {

  public function testFeatureBranch() {
    $git_info = $this->getMockBuilder(\Dorgflow\Service\GitInfo::class)
      ->disableOriginalConstructor()
      ->setMethods(['getBranchList', 'getCurrentBranch'])
      ->getMock();
    $git_info->method('getBranchList')
      ->willReturn([]);
    $git_info->method('getCurrentBranch')
      ->willReturn('notthebranchyouseek');

    $analyser = $this->getMockBuilder(\Dorgflow\Service\Analyser::class)
      ->disableOriginalConstructor()
      ->setMethods(['deduceIssueNumber'])
      ->getMock();
    $analyser->method('deduceIssueNumber')
      ->willReturn(123456);

    $drupal_org = $this->getMockBuilder(\Dorgflow\Service\DrupalOrg::class)
      ->disableOriginalConstructor()
      ->setMethods(['getIssueNodeTitle'])
      ->getMock();
    $drupal_org->method('getIssueNodeTitle')
      ->willReturn('the title of the issue');

    $git_exec = $this->getMockBuilder(\Dorgflow\Service\GitExecutor::class)
      ->disableOriginalConstructor()
      ->setMethods([]);

    $feature_branch = new \Dorgflow\Waypoint\FeatureBranch($git_info, $analyser, $drupal_org, $git_exec);

    $exists = $feature_branch->exists();
    $this->assertFalse($exists);

    $branch_name = $feature_branch->getBranchName();
    $this->assertEquals($branch_name, '123456-the-title-of-the-issue');
  }

}
