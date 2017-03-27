<?php

namespace Dorgflow\Tests;

/**
 * Unit test for the CommitMessageHandler service.
 *
 * Run with:
 * @code
 *   vendor/bin/phpunit tests/CommitMessageHandlerTest.php
 * @endcode
 */
class CommitMessageHandlerTest extends \PHPUnit\Framework\TestCase {

  /**
   * Tests parsing of commit messages.
   *
   * @dataProvider providerCommitMessages
   */
  public function testCommitMessageParser($message, $expected_data) {
    $analyser = $this->createMock(\Dorgflow\Service\Analyser::class);

    $commit_message_handler = new \Dorgflow\Service\CommitMessageHandler($analyser);

    $commit_data = $commit_message_handler->parseCommitMessage($message);

    // For ease of debugging failing tests, check each array item individually.
    if (is_array($expected_data)) {
      if (isset($expected_data['filename'])) {
        $this->assertEquals($expected_data['filename'], $commit_data['filename']);
      }
      if (isset($expected_data['fid'])) {
        $this->assertEquals($expected_data['fid'], $commit_data['fid']);
      }
    }


    // Check the complete expected data matches what we got, for return values
    // which are not arrays, and for completeness.
    $this->assertEquals($expected_data, $commit_data);
  }

  /**
   * Data provider for testCommitMessageParser().
   */
  public function providerCommitMessages() {
    return [
      'nothing' => [
        // Message.
        'Some other commit message.',
        // Expected data.
        FALSE,
      ],
      // 1.1.3 format.
      'd.org patch 1.1.3' => [
        // Message.
        'Patch from Drupal.org. Comment: 10; URL: https://www.drupal.org/node/12345#comment-67890; file: myfile.patch; fid: 16. Automatic commit by dorgflow.',
        // Expected data.
        [
          'filename' => 'myfile.patch',
          'fid' => 16,
          'comment_index' => 10,
        ],
      ],
      'local commit 1.1.3' => [
        // Message.
        'Patch for Drupal.org. Comment (expected): 10; file: 12345-10.project.bug-description.patch. Automatic commit by dorgflow.',
        // Expected data.
        [
          'filename' => '12345-10.project.bug-description.patch',
          'comment_index' => 10,
          'local' => TRUE,
        ],
      ],
      // 1.1.0 format.
      'local commit 1.1.0' => [
        // Message.
        'Patch for Drupal.org. File: 12345-10.project.bug-description.patch. Automatic commit by dorgflow.',
        // Expected data.
        [
          'filename' => '12345-10.project.bug-description.patch',
          'local' => TRUE,
          // The parser extracts this from the filename for the 1.1.0 format.
          'comment_index' => 10,
        ],
      ],
      // 1.0.0 format.
      'd.org patch 1.0.0' => [
        // Message.
        'Patch from Drupal.org. Comment: 10; file: myfile.patch; fid 16. Automatic commit by dorgflow.',
        // Expected data.
        [
          'filename' => 'myfile.patch',
          'fid' => 16,
          'comment_index' => 10,
        ],
      ],
    ];
  }

}
