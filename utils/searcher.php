<?php
/*
 * gets raw data and search it
 */

class searcher {
    
	public $searchString;
	
    public $src;
    public $rawData;
    
    public $season;  
    public $episodeFirst;
    public $episodeLast;
    public $year;
    
    public $name;
    public $nameEng;
    
    public $schema = '';
    
    public $urls;
    
    
    public function __construct($data) {
        $this->rawData = $data;
        return $this->transform();
    }
    
    private function transform() {
        
        $this->name = $this->rawData['ruTitle'];
        $this->nameEng = $this->rawData['title'];
        
        if (empty($this->name) && !empty($this->nameEng)) {
        	$this->name = $this->nameEng;
        }
        
        if (empty($this->nameEng) && !empty($this->name)) {
        	$this->nameEng = $this->name;
        }
        
        //всегда первый по номеру сезон
        $this->season = $this->rawData['episodes'][0]->seasonNumber;
        $this->episodeFirst = $this->rawData['episodes'][0]->episodeNumber;
        $this->year = strtotime($this->rawData['episodes'][0]->airDate);
        //всегда последний эпизод
        foreach ($this->rawData['episodes'] as $item) {
            if ($this->season == $item->seasonNumber) {
                $this->episodeLast = $item->episodeNumber;
            }
        }
        
        return $this;
    }
    
    public function search ($ru = true, $last = true, $series = true) {
        $ss = array();
        
        $this->urls = null;
        $this->schema = '';
        $this->schema .= intval($ru);
        $this->schema .= intval($last);
        
        if ($ru) {
            $ss[]= mb_strtolower($this->name, 'UTF-8');
        } else {
            $ss[]= mb_strtolower($this->nameEng, 'UTF-8');
        }

        $ss[]= "сезон {$this->season}";
        
        if ($series) {
	        if ($last) {
	            $ss[]= "cерия {$this->episodeLast}";
	        } else {
	            $ss[]= "cерия {$this->episodeFirst}";
	        }
        }
        
        $this->searchString = implode(' ', $ss);
        $searchString = urlencode($this->searchString);
        $html = file_get_html('http://www.ex.ua/search?s='.$searchString);
        if (is_object($html)) {
        	$links = $html->find('table.panel td a');
		    $looker = ($ru)? $this->name : $this->nameEng ;
		    
        	if (is_array($links) && !empty($links)) {
		        foreach ($links as $el) {
		        	$text = $el->plaintext;
		        	if (mb_stripos($text, $looker, 0, 'UTF-8') !== false) {
			            $this->urls[] = array(
			                'href' => $el->href,
			                'text' => mb_strtolower($el->plaintext, 'UTF-8')
			            );
		        	}
		        }
		        if (!empty($this->urls)) {
			        $tmp = new ranker($this); 
		        	return $tmp->rankAll();
		        }
	        }
        }
        
        return null;
        
    }
    
    public function promote($key, $rank = 0) {
    	$this->urls[$key]['rank'] = $rank;
        return $key!==-1? $this->urls[$key] : null;

    }
    
    public function setRank($key, $rank = 0) {
    	$this->urls[$key]['rank'] = $rank;
    	return true;
    }
    
}