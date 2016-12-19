<?php

namespace Dorgflow\Testing\Fetcher;

use Dorgflow\Situation;
use Dorgflow\DataSource\Fetcher\FetcherInterface;

/**
 * Retrieves an issue node from drupal.org.
 */
class DummyIssueNodeMultipleFiles implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetchData(Situation $situation, $parameters) {
    // Data from node 2801423.
    return (object) (array(
       'body' =>
      (object) (array(
         'value' => '<p>isFlagged status is not reflected correctly in some cases, most cases of this will incorrectly show the wrong flag status until the page is refreshed.</p>
    <p>Two calls to $flag-&gt;isFlagged($entity) that span a Flagging save or a Flagging delete operation will not update the isFlagged status.</p>
    <p>Ex.<br />
    $flag-&gt;isFlagged($entity);  (returns Not Flagged)<br />
    Call to FlagService flag with $entity.<br />
    $flag-&gt;isFlagged($entity);  (returns Not Flagged Incorrectly)</p>
    <p>OR</p>
    <p>$flag-&gt;isFlagged($entity);  (returns Flagged)<br />
    Delete Flagging for $entity for $flag_id<br />
    $flag-&gt;isFlagged($entity);  (returns Flagged Incorrectly)</p>
    <p>Since the addition of the FlaggingStorage (<a href="https://www.drupal.org/commitlog/commit/6408/cf93be36cd4f89a0f6ddafe0bbbdf771872572cd" rel="nofollow">https://www.drupal.org/commitlog/commit/6408/cf93be36cd4f89a0f6ddafe0bbb...</a>) this has been an issue.</p>',
         'summary' => '',
         'format' => '1',
      )),
       'taxonomy_vocabulary_9' =>
      array (
      ),
       'field_issue_status' => '8',
       'field_issue_priority' => '200',
       'field_issue_category' => '1',
       'field_issue_component' => 'Flag core',
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
             'uri' => 'https://www.drupal.org/api-d7/file/5699555',
             'id' => '5699555',
             'resource' => 'file',
          )),
           'display' => '0',
        )),
        1 =>
        (object) (array(
           'file' =>
          (object) (array(
             'uri' => 'https://www.drupal.org/api-d7/file/5746785',
             'id' => '5746785',
             'resource' => 'file',
          )),
           'display' => '0',
        )),
        2 =>
        (object) (array(
           'file' =>
          (object) (array(
             'uri' => 'https://www.drupal.org/api-d7/file/5746786',
             'id' => '5746786',
             'resource' => 'file',
          )),
           'display' => '1',
        )),
        3 =>
        (object) (array(
           'file' =>
          (object) (array(
             'uri' => 'https://www.drupal.org/api-d7/file/5746787',
             'id' => '5746787',
             'resource' => 'file',
          )),
           'display' => '1',
        )),
        4 =>
        (object) (array(
           'file' =>
          (object) (array(
             'uri' => 'https://www.drupal.org/api-d7/file/5747266',
             'id' => '5747266',
             'resource' => 'file',
          )),
           'display' => '1',
        )),
        5 =>
        (object) (array(
           'file' =>
          (object) (array(
             'uri' => 'https://www.drupal.org/api-d7/file/5747267',
             'id' => '5747267',
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
        0 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11631183',
           'id' => '11631183',
           'resource' => 'comment',
        )),
        1 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11631209',
           'id' => '11631209',
           'resource' => 'comment',
        )),
        2 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11631347',
           'id' => '11631347',
           'resource' => 'comment',
        )),
      ),
       'flag_drupalorg_node_spam_user' =>
      array (
      ),
       'flag_project_issue_follow_user' =>
      array (
        0 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/user/180064',
           'id' => 180064,
           'resource' => 'user',
        )),
        1 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/user/208732',
           'id' => 208732,
           'resource' => 'user',
        )),
        2 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/user/1270322',
           'id' => 1270322,
           'resource' => 'user',
        )),
        3 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/user/65793',
           'id' => 65793,
           'resource' => 'user',
        )),
        4 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/user/2659379',
           'id' => 2659379,
           'resource' => 'user',
        )),
        5 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/user/559632',
           'id' => 559632,
           'resource' => 'user',
        )),
      ),
       'nid' => '2801423',
       'vid' => '10220720',
       'is_new' => false,
       'type' => 'project_issue',
       'title' => 'FlaggingStorage does not update cached Flagging status.',
       'language' => 'en',
       'url' => 'https://www.drupal.org/node/2801423',
       'edit_url' => 'https://www.drupal.org/node/2801423/edit',
       'status' => '1',
       'promote' => '0',
       'sticky' => '0',
       'created' => '1474042917',
       'changed' => '1479926083',
       'author' =>
      (object) (array(
         'uri' => 'https://www.drupal.org/api-d7/user/559632',
         'id' => '559632',
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
           'uri' => 'https://www.drupal.org/api-d7/comment/11631183',
           'id' => 11631183,
           'resource' => 'comment',
        )),
        1 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11631209',
           'id' => 11631209,
           'resource' => 'comment',
        )),
        2 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11631347',
           'id' => 11631347,
           'resource' => 'comment',
        )),
        3 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11635019',
           'id' => 11635019,
           'resource' => 'comment',
        )),
        4 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11791959',
           'id' => 11791959,
           'resource' => 'comment',
        )),
        5 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11792155',
           'id' => 11792155,
           'resource' => 'comment',
        )),
        6 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11792161',
           'id' => 11792161,
           'resource' => 'comment',
        )),
        7 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11792162',
           'id' => 11792162,
           'resource' => 'comment',
        )),
        8 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11793587',
           'id' => 11793587,
           'resource' => 'comment',
        )),
        9 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11793670',
           'id' => 11793670,
           'resource' => 'comment',
        )),
        10 =>
        (object) (array(
           'uri' => 'https://www.drupal.org/api-d7/comment/11793759',
           'id' => 11793759,
           'resource' => 'comment',
        )),
      ),
       'comment_count' => '11',
       'comment_count_new' => false,
       'feed_nid' => NULL,
       'flag_flag_tracker_follow_user' =>
      array (
      ),
       'has_new_content' => NULL,
       'last_comment_timestamp' => '1479927345',
    ));
  }

}
