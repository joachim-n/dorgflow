<?php

/**
 * Services definition.
 */

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;

/*
waypoint manager: git info...
git info (logs etc): analyser (for the current branch),
analyser: d.org, git status
d.org API (static caches things it fetches): git status
git status (diff status and current branch)
user input
current folder
git executor
patch name handler
*/

$container
  ->register('user_input', '\Dorgflow\Service\UserInput');

$container
  ->register('git.info', '\Dorgflow\Service\GitInfo');

$container
  ->register('git.executor', '\Dorgflow\Executor\Git');

$container
  ->register('commit_message', \Dorgflow\Service\CommitMessageHandler::class);

$container
  ->register('analyser', '\Dorgflow\Service\Analyser')
  ->addArgument(new Reference('git.info'))
  ->addArgument(new Reference('user_input'));

$container
  ->register('drupal_org', '\Dorgflow\Service\DrupalOrg')
  ->addArgument(new Reference('analyser'));

$container
  ->register('waypoint_manager.branches', \Dorgflow\Service\WaypointManagerBranches::class)
  ->addArgument(new Reference('git.info'))
  ->addArgument(new Reference('drupal_org'))
  ->addArgument(new Reference('git.executor'));

$container
  ->register('git.log', '\Dorgflow\Service\GitLog')
  ->addArgument(new Reference('waypoint_manager.branches'));

$container
  ->register('waypoint_manager.patches', \Dorgflow\Service\WaypointManagerPatches::class)
  ->addArgument(new Reference('commit_message'))
  ->addArgument(new Reference('drupal_org'))
  ->addArgument(new Reference('git.log'))
  ->addArgument(new Reference('git.executor'))
  ->addArgument(new Reference('waypoint_manager.branches'));
