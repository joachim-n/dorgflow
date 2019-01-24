<?php

/**
 * Services definition.
 */

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;

$container
  ->register('user_input', \Dorgflow\Service\UserInput::class);

$container
  ->register('git.info', \Dorgflow\Service\GitInfo::class);

$container
  ->register('git.executor', \Dorgflow\Service\GitExecutor::class)
  ->addArgument(new Reference('git.info'));

$container
  ->register('analyser', \Dorgflow\Service\Analyser::class)
  ->addArgument(new Reference('git.info'))
  ->addArgument(new Reference('user_input'));

$container
  ->register('commit_message', \Dorgflow\Service\CommitMessageHandler::class)
  ->addArgument(new Reference('analyser'));

$container
  ->register('drupal_org', \Dorgflow\Service\DrupalOrg::class)
  ->addArgument(new Reference('analyser'));

$container
  ->register('waypoint_manager.branches', \Dorgflow\Service\WaypointManagerBranches::class)
  ->addArgument(new Reference('git.info'))
  ->addArgument(new Reference('drupal_org'))
  ->addArgument(new Reference('git.executor'))
  ->addArgument(new Reference('analyser'));

$container
  ->register('git.log', \Dorgflow\Service\GitLog::class)
  ->addArgument(new Reference('waypoint_manager.branches'));

$container
  ->register('waypoint_manager.patches', \Dorgflow\Service\WaypointManagerPatches::class)
  ->addArgument(new Reference('commit_message'))
  ->addArgument(new Reference('drupal_org'))
  ->addArgument(new Reference('git.log'))
  ->addArgument(new Reference('git.executor'))
  ->addArgument(new Reference('analyser'))
  ->addArgument(new Reference('waypoint_manager.branches'));

// Register commands as services.
$container
  ->register('command.apply', \Dorgflow\Command\Apply::class)
  ->addMethodCall('setContainer', [new Reference('service_container')]);
$container
  ->register('command.cleanup', \Dorgflow\Command\Cleanup::class)
  ->addMethodCall('setContainer', [new Reference('service_container')]);
$container
  ->register('command.status', \Dorgflow\Command\Status::class)
  ->addMethodCall('setContainer', [new Reference('service_container')]);
$container
  ->register('command.diff', \Dorgflow\Command\Diff::class)
  ->addMethodCall('setContainer', [new Reference('service_container')]);
$container
  ->register('command.patch', \Dorgflow\Command\CreatePatch::class)
  ->addMethodCall('setContainer', [new Reference('service_container')]);
$container
  ->register('command.setup', \Dorgflow\Command\LocalSetup::class)
  ->addMethodCall('setContainer', [new Reference('service_container')]);
$container
  ->register('command.update', \Dorgflow\Command\LocalUpdate::class)
  ->addMethodCall('setContainer', [new Reference('service_container')]);
$container
  ->register('command.master', \Dorgflow\Command\SwitchMaster::class)
  ->addMethodCall('setContainer', [new Reference('service_container')]);
$container
  ->register('command.purge', \Dorgflow\Command\Purge::class)
  ->addMethodCall('setContainer', [new Reference('service_container')]);
