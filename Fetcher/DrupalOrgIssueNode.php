<?php

namespace Dorgflow\Fetcher;

class DrupalOrgIssueNode {
  
  public function getIssueNumber() {
    // TODO? Needed?
  }
  
  public function getPatchList() {

  }
  
  public function getIssueNodeTitle() {
    return 'foobar is broken';
  }
  
  // methods needed:
  
  // next comment number
  
  // file list
  
  // most recent file
  

  // TODO!
  protected function fetchData() {
    /*
    
    use EclipseGc\DrupalOrg\Api\DrupalClient;

    $client = DrupalClient::create();

    $node = $client->getNode(4);
    */
  }

}
