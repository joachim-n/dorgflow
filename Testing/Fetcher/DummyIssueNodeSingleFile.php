<?php

namespace Dorgflow\Testing\Fetcher;

use Dorgflow\Situation;
use Dorgflow\DataSource\Fetcher\FetcherInterface;

/**
 * Retrieves an issue node from drupal.org.
 */
class DummyIssueNodeSingleFile implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetchData(Situation $situation, $parameters) {
    // Data from node 2833387.
    dump('fetched dummy!');
    return (object) (array(
       'taxonomy_vocabulary_9' =>
      array (
        0 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/taxonomy_term/178573',
           'id' => '178573',
           'resource' => 'taxonomy_term',
        )),
      ),
       'body' =>
      (object) (array(
         'value' => '<p>Hi!</p>
    <p>Congrats on the module. He is excellent.</p>
    <p>I fixed the variable unused in code.</p>
    <p>The patch with fix it\'s in attachment.</p>
    <p>Thanks.</p>
    <p>Regards.</p>',
         'summary' => '',
         'format' => '1',
      )),
       'field_issue_status' => '14',
       'field_issue_priority' => '200',
       'field_issue_category' => '1',
       'field_issue_component' => 'Views integration',
       'field_project' =>
      (object) (array(
         'uri' => 'https://www.drupal.org/api-d7/node/268362',
         'id' => '268362',
         'resource' => 'node',
      )),
       'field_issue_files' =>
      array (
        0 =>
        (object) (array(
           'file' =>
          (object) (array(
             'uri' => 'https://www.drupal.org/api-d7/file/5753001',
             'id' => '5753001',
             'resource' => 'file',
          )),
           'display' => '1',
        )),
      ),
       'field_issue_related' =>
      array (
      ),
       'field_issue_version' => '8.x-4.x-dev',
       'field_issue_credit' =>
      array (
      ),
       'flag_drupalorg_node_spam_user' =>
      array (
      ),
       'flag_project_issue_follow_user' =>
      array (
        0 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/user/3410237',
           'id' => 3410237,
           'resource' => 'user',
        )),
        1 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/user/3326031',
           'id' => 3326031,
           'resource' => 'user',
        )),
      ),
       'nid' => '2833387',
       'vid' => '10238813',
       'is_new' => false,
       'type' => 'project_issue',
       'title' => 'Unused variable $flag_id',
       'language' => 'en',
       'url' => 'https://www.drupal.org/node/2833387',
       'edit_url' => 'https://www.drupal.org/node/2833387/edit',
       'status' => '1',
       'promote' => '0',
       'sticky' => '0',
       'created' => '1481022558',
       'changed' => '1481046172',
       'author' =>
      (object) (array(
         'uri' => 'https://www.drupal.org/api-d7/user/3326031',
         'id' => '3326031',
         'resource' => 'user',
      )),
       'book_ancestors' =>
      array (
      ),
       'comment' => '2',
       'comments' =>
      array (
        0 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11812703',
           'id' => 11812703,
           'resource' => 'comment',
        )),
        1 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11813488',
           'id' => 11813488,
           'resource' => 'comment',
        )),
      ),
       'comment_count' => '2',
       'comment_count_new' => false,
       'feed_nid' => NULL,
       'flag_flag_tracker_follow_user' =>
      array (
      ),
       'has_new_content' => NULL,
       'last_comment_timestamp' => '1481046172',
    ));
  }

}
