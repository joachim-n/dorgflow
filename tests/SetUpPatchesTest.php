<?php

namespace Dorgflow;

/**
 * Tests creating patch objects with the patch manager.
 *
 * Run with:
 * @code
 *   vendor/bin/phpunit tests/SetUpPatchesTest.php
 * @endcode
 */
class SetUpPatchesTest extends \PHPUnit\Framework\TestCase {

  /**
   * Test patches with no local branch.
   */
  public function testPatchesNoLog() {
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
           'cid' => '400',
        )),
         'display' => '0',
         'index' => 1,
      )),
      // Not a patch file: should be omitted once the file entity has been seen.
      1 =>
      (object) (array(
         'file' =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/file/5755137',
           'id' => '101',
           'resource' => 'file',
           'cid' => '401',
        )),
         'display' => '1',
         'index' => 2,
      )),
      2 =>
      (object) (array(
         'file' =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/file/5755185',
           'id' => '102',
           'resource' => 'file',
           'cid' => '402',
        )),
         'display' => '1',
         'index' => 4,
      )),
      3 =>
      (object) (array(
         'file' =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/file/5755421',
           'id' => '103',
           'resource' => 'file',
           'cid' => '403',
        )),
         'display' => '1',
         'index' => 10,
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
      NULL,
      NULL,
      NULL
    );

    $patches = $wmp->setUpPatches();

    $analyser = $this->createMock(\Dorgflow\Service\Analyser::class);
    $analyser->method('deduceIssueNumber')
      ->willReturn(123456);

    $commit_message_handler = new \Dorgflow\Service\CommitMessageHandler($analyser);

    $this->assertCount(2, $patches);

    $patch_102 = $patches[0];
    $this->assertEquals($file_urls[102], $patch_102->getPatchFilename());
    $this->assertEquals("Patch from Drupal.org. Comment: 4; URL: https://www.drupal.org/node/123456#comment-402; file: fix-102.patch; fid: 102. Automatic commit by dorgflow.",
      $commit_message_handler->createCommitMessage($patch_102));

    $patch_103 = $patches[1];
    $this->assertEquals($file_urls[103], $patch_103->getPatchFilename());
    $this->assertEquals("Patch from Drupal.org. Comment: 10; URL: https://www.drupal.org/node/123456#comment-403; file: fix-103.patch; fid: 103. Automatic commit by dorgflow.",
      $commit_message_handler->createCommitMessage($patch_103));

    return;
  }

  /**
   * Test the patchFilenamesAreEqual() helper method.
   *
   * @dataProvider providerPatchFileComparison
   *
   * @todo Move this somewhere better.
   */
  public function testPatchFileComparison($local_filename, $drupal_org_filename, $expected_result) {
    $wmp = new \Dorgflow\Service\WaypointManagerPatches(
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      NULL
    );

    $reflection = new \ReflectionClass(\Dorgflow\Service\WaypointManagerPatches::class);
    $method = $reflection->getMethod('patchFilenamesAreEqual');
    $method->setAccessible(TRUE);

    $result = $method->invokeArgs($wmp, [$local_filename, $drupal_org_filename]);

    $this->assertEquals($expected_result, $result, "The filenames $local_filename and $drupal_org_filename did not produce the expected result.");
  }

  /**
   * Data provider for testCommitMessageParser().
   */
  public function providerPatchFileComparison() {
    return [
      'equal' => [
        'foo.patch',
        'foo.patch',
        // Expected result.
        TRUE,
      ],
      'munged' => [
        'foo.flag.patch',
        'foo.flag_.patch',
        TRUE,
      ],
      'renamed' => [
        'foo.longer-piece.patch',
        'foo.longer-piece_12.patch',
        TRUE,
      ],
      'munged and renamed' => [
        'foo.flag.longer-piece.patch',
        'foo.flag_.longer-piece_12.patch',
        TRUE,
      ],
      'different' => [
        'foo.flag.patch',
        'totally.different.flag.patch',
        FALSE,
      ],
    ];
  }

}
