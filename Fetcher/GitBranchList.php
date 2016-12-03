<?php

namespace Dorgflow\Fetcher;

use Dorgflow\Parser\GitBranchList as GitBranchListParser;

// Fetchers lazy load and cache in themselves!
// Fetchers are responsible for one source of data, though they may provide
// more than one piece of data from it.
/*
argh, which way round should this be? fethcer or parser on the outside?
for tests, I want to isolate the parser.
So I want a parser constructed without a fetcher, or with the fetcher I specify...

and when should fetching happen -- on first call, or on construct? how does on construct
affect testing? TRY WRITING SOME TESTS TO SEE!

also, higher-level tests will need to use more stuff, and cut out the fetchers.
which suggests fetchers are the last piece.





*/
class GitBranchList {
  
  protected $parser_class = GitBranchListParser::class;
  
  protected $parser = NULL;
  
  // TODO: might as well fetch data on creation, as if you create this it's 
  // because you want something from it.
  // but will that mess up testing?

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
