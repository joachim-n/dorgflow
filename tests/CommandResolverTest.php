<?php

namespace Dorgflow\Tests;

/**
 * Tests the CommandResolver class.
 *
 * Run with:
 * @code
 *   vendor/bin/phpunit tests/CommandResolverTest.php
 * @endcode
 */
class CommandResolverTest extends \PHPUnit_Framework_TestCase {

  /**
   * @dataProvider provider
   */
  public function testCommandClass($arguments, $expected_short_class_name) {
    // $argv has the script name in first position, so add that in.
    array_unshift($arguments, 'script_name');

    $command_class_name = \Dorgflow\CommandResolver::getCommandClass($arguments);

    $expected_class_name = "\\Dorgflow\\Command\\$expected_short_class_name";

    $this->assertEquals($expected_class_name, $command_class_name);
  }

  public function provider() {
    return [
      'setup node id' => [
        [12345],
        'LocalSetup',
      ],
      'setup url' => [
        ['https://www.drupal.org/node/12345'],
        'LocalSetup',
      ],
      'create patch' => [
        [],
        'CreatePatch',
      ],
      'update' => [
        ['update'],
        'LocalUpdate',
      ],
      'apply' => [
        ['apply'],
        'Apply',
      ],
    ];
  }

}
