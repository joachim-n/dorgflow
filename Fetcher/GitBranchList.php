<?php

namespace Dorgflow\Fetcher;

use Dorgflow\Parser\GitBranchList as GitBranchListParser;

// Fetchers lazy load and cache in themselves!
// Fetchers are responsible for one source of data, though they may provide
// more than one piece of data from it.
class GitBranchList {
  
  protected $parser_class = GitBranchListParser::class;
  
  protected $parser = NULL;

  public function getBranchList() {
    if (!isset($this->parser)) {
      $this->fetchData();
    }
    
    return $this->parser->get('branchList');
  }
  
  public function getCurrentBranch() {
    if (!isset($this->parser)) {
      $this->fetchData();
    }
    
    return $this->parser->get('currentBranch');
  }
  
  protected function fetchData() {
    // TODO: check in right dir!
    
    // Get the branches that are reachable.
    $branch_list = shell_exec("git branch --merged");
    
    $this->parser = new $this->parser_class;
    
    $this->parser->setInput($branch_list);
    $this->parser->parse();
  }

}
