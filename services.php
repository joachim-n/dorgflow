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
  ->register('git.executor', \Dorgflow\Service\GitExecutor::class);

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
