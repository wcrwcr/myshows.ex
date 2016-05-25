<?php
class linkRetriever {

	var $pageUrl;
	
	var $page;
	
	private function _num($num){
		return ($num/10<1) ? "0{$num}" : $num; 
	}
	
	public function __construct($url) {
		$this->pageUrl = $url;
		return $this;
	}
	
	function getPage(){
		$this->page = file_get_html($this->pageUrl);
	}
	
	function stripLinks($data) {
		$this->getPage();
		$season = $data->season;
		$links = array();
		for ($episode = $data->episodeLast; $episode<= $data->episodeFirst; $episode++) {
			foreach (array(
					"s".$this->_num($season)."e".$this->_num($episode),
					"S".$this->_num($season)."E".$this->_num($episode),
			        "e".$this->_num($episode)."s".$this->_num($season),
			        "E".$this->_num($episode)."S".$this->_num($season)
			 ) as $k=>$searchString) {
			    			    
			 	$fnd = $this->page->find("a[title*={$searchString}]", 0);
			 	if ($fnd !== null) {
			 	    dumpIncremental("lookforLink `{$searchString}` s{$season}e{$episode}", '_#ranklogPromoted.log');
			 		$links[$season.'-'.$episode] = $fnd->href;
			 		dumpIncremental("and found {$fnd->innertext} s{$season}e{$episode}", '_#ranklogPromoted.log');
			 	}
			 }
		}
		
		return $links;
	}
}