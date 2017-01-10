<?php

namespace Dorgflow;

/**
 * Tests the GitBranchList class.
 *
 * Run with:
 * @code
 *   vendor/bin/phpunit tests/SetUpPatchesTest.php
 * @endcode
 */
class SetUpPatchesTest extends \PHPUnit_Framework_TestCase {

  /**
   * Test patches with no local branch.
   */
  public function testSinglePatchNoLog() {
    // The file field items for the issue node.
    $issue_file_field_items =
    array (
      // Not displayed: should be omitted, and the file entity not retrieved.
      0 =>
      (object) (array(
         'file' =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/file/5755031',
           'id' => '100',
           'resource' => 'file',
        )),
         'display' => '0',
      )),
      // Not a patch file: should be omitted once the file entity has been seen.
      1 =>
      (object) (array(
         'file' =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/file/5755137',
           'id' => '101',
           'resource' => 'file',
        )),
         'display' => '1',
      )),
      2 =>
      (object) (array(
         'file' =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/file/5755185',
           'id' => '102',
           'resource' => 'file',
        )),
         'display' => '1',
      )),
      3 =>
      (object) (array(
         'file' =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/file/5755421',
           'id' => '103',
           'resource' => 'file',
        )),
         'display' => '1',
      )),
    );

    $file_urls = [
      101 => 'foobar.notapatch.txt',
      102 => 'fix-102.patch',
      103 => 'fix-103.patch',
    ];

    $drupal_org = $this->getMockBuilder(\Dorgflow\Service\DrupalOrg::class)
      ->disableOriginalConstructor()
      ->setMethods(['getIssueFileFieldItems', 'getFileEntity'])
      ->getMock();
    $drupal_org->method('getIssueFileFieldItems')
      ->willReturn($issue_file_field_items);
    $drupal_org->expects($this->any())
      ->method('getFileEntity')
      ->will($this->returnValueMap([
        // Note the params have to be strings, not numeric!
        // For dummy file entities, we only need the url property.
        ['101', (object) ['url' => $file_urls[101]]],
        ['102', (object) ['url' => $file_urls[102]]],
        ['103', (object) ['url' => $file_urls[103]]],
      ]));

    $git_log = $this->getMockBuilder(\Dorgflow\Service\GitLog::class)
      ->disableOriginalConstructor()
      ->setMethods(['getFeatureBranchLog'])
      ->getMock();
    $git_log->method('getFeatureBranchLog')
      ->willReturn([]);

    $wmp = new \Dorgflow\Service\WaypointManagerPatches(
      NULL,
      $drupal_org,
      $git_log,
      NULL,
      NULL
    );

    $patches = $wmp->setUpPatches();

    $this->assertCount(2, $patches);

    $patch_102 = $patches[0];
    $this->assertEquals($file_urls[102], $patch_102->getPatchFilename());

    $patch_103 = $patches[1];
    $this->assertEquals($file_urls[103], $patch_103->getPatchFilename());

    return;
  }

}
