<?php
/*
 * gets raw data and search it
 */

class ranker {

    private $rankLimit = 350;
    
    private $links;
    private $res;
    
    private $baseRank = 100;
    
//    public function __construct(searcher $data) {
    public function __construct($data) {
        $this->links = $data;
        return $this;
    }
    
    public function rankAll() {
        $ranked = false;
        $max = $maxRanked = 0;
        foreach ($this->links->urls as $key => $url) {
            $rank = 0;
            $ranked = array();
            foreach (array('Name', 'Season', 'Episode', 'Weight', 'Studio') as $method) {
            	$rt = call_user_func_array(array($this, "rank{$method}"), array($url['text']));
            	$rank += $rt;
            	$ranked[$method] = $rt;
            }
            $ranked['total'] = $rank;

            //set Ranked.Episode and Ranked.Season most important
            if ($rank > $max && $ranked['Episode'] > 0 && $ranked['Season'] > 0) {
                $max = $rank;
                $maxRanked = $ranked;
                $this->res = $key; 
            }
            
            dumpIncremental("{$this->links->name} for url:{$url['text']} ranked:", '_#ranklogAll.log');
            dumpIncremental(print_r($ranked, 1), '_#ranklogAll.log');
             
            $this->links->setRank($key, $ranked);
            
        }
        
        if ($max >= $this->rankLimit) {
            dumpIncremental("{$this->links->name} promoted with:{$this->links->urls[$this->res]} ranked:", '_#ranklogPromoted.log');
            dumpIncremental(print_r($maxRanked, 1), '_#ranklogPromoted.log');
            return $this->links->promote($this->res, $maxRanked);
        }
        
        return null;
    }
    
    private function rankName($url) {
        $rank = 0;
        
        if (false !== mb_stripos($url, stripSS($this->links->name), 0, 'UTF-8')){
            $rank += 50;
        }
        
        if(false !== mb_stripos($url, stripSS($this->links->nameEng), 0, 'UTF-8')) {
            $rank += 50;
        }
        return $rank;
    }
    
    function rankSeason($url) {
        foreach (array(
            "сезон ",
            "сезон: ",
            "сезон",
            "сезон:",
            "сезон-",
            "сезон - ",
            "Cезон ",
            "Cезон: ",
            "Cезон",
            "Cезон:",
            "Cезон-",
            "Cезон - "
        ) as $pattern) {
        
            if (false !== mb_stripos($url , "{$pattern}{$this->links->season}", 0, 'UTF-8')){
                dumpIncremental("{$this->links->name} for url:{$url} ranked by pattern:{$pattern}{$this->links->season}", '_#ranklogSeason.log');
                return $this->baseRank;
            }
        }
        if(false !== mb_stripos($url, "{$this->links->season} сезон", 0, 'UTF-8')) {
    		dumpIncremental("{$this->links->name} for url:{$url} ranked by pattern:{$this->links->season} сезон", '_#ranklogSeason.log');
            return $this->baseRank;
        }
        
        /* this cause problems
        if(false !== mb_stripos($url , "полный сезон", 0, 'UTF-8')) {
    		dumpIncremental("{$this->links->name} for url:{$url} ranked by pattern:полный сезон", '_#ranklogSeason.log');
            return $this->baseRank;
        }
        */
        return 0;
        
    }
    
    public function rankEpisode($url) {
    	foreach (array("серии", "серия", "Cерии", "Cерия") as $prePrePattern) {
    		foreach (array(" ", ": ") as $middlePattern) {
    			foreach (array("1-", "1 - ", "1 -") as $postPattern) {
    				$prePattern = $prePrePattern.$middlePattern.$postPattern;
    				
    				if(false !== mb_stripos($url , $prePattern, 0, 'UTF-8')) {
    					if(false !== mb_stripos($url , $prePattern.$this->links->episodeLast, 0, 'UTF-8')) {
    					    dumpIncremental("{$this->links->name} for url:{$url} ranked by pre pattern {$prePattern} + {$this->links->episodeLast}", '_#ranklogSeries.log');
    					    return $this->baseRank;
    					}elseif(false !== mb_stripos($url , $prePattern.$this->links->episodeFirst, 0, 'UTF-8')) {
    					    dumpIncremental("{$this->links->name} for url:{$url} ranked by pre pattern {$prePattern} + {$this->links->episodeFirst}", '_#ranklogSeries.log');
    						return $this->baseRank;
    					}else {
    						preg_match('/('.$prePattern.'[?<digit>\d+]*)/', $url, $matches);
    						$dd = array_map('trim', explode('-',$matches[0]));
    						$lastnum = intval($dd[0]);
    						if($lastnum >= $this->links->episodeLast) {
    						    dumpIncremental("{$this->links->name} for url:{$url} ranked by preg lastnum:{$lastnum} >= {$this->links->episodeLast}", '_#ranklogSeries.log');    						
    						    return $this->baseRank;
    						}
    						if($lastnum >= $this->links->episodeFirst) {
    						    dumpIncremental("{$this->links->name} for url:{$url} ranked by preg lastnum:{$lastnum} >= {$this->links->episodeFirst}", '_#ranklogSeries.log');
    							return $this->baseRank;
    						}
    					}
    				}
    				
    			}
    		}
    	}
        return 0;
    
    }

    private function rankWeight($url) {
        foreach (array(
            'hdtv 1080' => $this->baseRank*0.75,
            'webdl 1080' => $this->baseRank*0.75,
            'web-dl 1080' => $this->baseRank*0.75,
            'web-dlrip 1080' => $this->baseRank*0.75,
            'hdtv 720' => $this->baseRank*0.85,
            'webdl 720' => $this->baseRank*0.85,
            'web-dl 720' => $this->baseRank*0.85,
            'web-dlrip 720' => $this->baseRank*0.85,
            'hdtvrip' => $this->baseRank,
            'web-dlrip' => $this->baseRank,
            'web-dl' => $this->baseRank,
            'webdlrip' => $this->baseRank,
            'hdtvrip' => $this->baseRank,
            'hdtv' => $this->baseRank
        ) as $str => $rank) {
            if(false !== mb_stripos($url , $str, 0, 'UTF-8')) {
            	return $rank;
            }
        }
    
        return 0;
    }

    private function rankStudio($url) {
        foreach (array(
            'lostfilm' => $this->baseRank*1.3,
            'novafilm' => $this->baseRank*1.3,            
            'newstudio' => $this->baseRank*1.3,
            'кураж бамбей'=>$this->baseRank*1.4,
            'кураж-бамбей'=>$this->baseRank*1.4,
            'kuraj-bambey'=>$this->baseRank*1.4,
            'кубик в кубе' => $this->baseRank*1.3,
            'baibak & ko' => $this->baseRank,
            'baibak&ko' => $this->baseRank,
            'baibako' => $this->baseRank,
        	'ozz.tv'=> $this->baseRank,
            'alexfim' => $this->baseRank,
            'sienduk' => $this->baseRank*0.9,
            'amedia' => $this->baseRank*0.7,
        ) as $str => $rank) {
            if(false !== mb_stripos($url , $str, 0, 'UTF-8')) {
            	return $rank;
            }
        }
    
        return 0;
    }
    
}