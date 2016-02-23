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

            //set Ranked.Episode most important
            if ($rank > $max && $ranked['Episode'] > 0) {
                $max = $rank;
                $maxRanked = $ranked;
                $this->res = $key; 
            }
            $this->links->setRank($key, $ranked);
            
        }
        
        if ($max >= $this->rankLimit) {
        	return $this->links->promote($this->res, $maxRanked);
        }
        
        return -1;
        
        
    }
    
    private function rankName($url) {
        $rank = 0;
        
        if (false !== mb_stripos($url , $this->links->name, 0, 'UTF-8')){
            $rank += 50;
        }
        
        if(false !== mb_stripos($url , $this->links->nameEng, 0, 'UTF-8')) {
            $rank += 50;
        }
        return $rank;
    }
    
    function rankSeason($url) {
        if (false !== mb_stripos($url , "сезон {$this->links->season}", 0, 'UTF-8') || false !== mb_stripos($url , "сезон: {$this->links->season}", 0, 'UTF-8')){
            return $this->baseRank;
        }
        
        if(false !== mb_stripos($url, "{$this->links->season} сезон", 0, 'UTF-8')) {
            return $this->baseRank;
        }
        
        if(false !== mb_stripos($url , "полный сезон", 0, 'UTF-8')) {
            return $this->baseRank;
        }
        
        return 0;
        
    }
    
    public function rankEpisode($url) {
    	foreach (array("серии", "серия") as $prePrePattern) {
    		foreach (array(" ", ": ") as $middlePattern) {
    			foreach (array("1-", "1 - ") as $postPattern) {
    				$prePattern = $prePrePattern.$middlePattern.$postPattern;
    				
    				if(false !== mb_stripos($url , $prePattern, 0, 'UTF-8')) {
    					if(false !== mb_stripos($url , $prePattern.$this->links->episodeLast, 0, 'UTF-8')) {
    						return $this->baseRank*2;
    					}elseif(false !== mb_stripos($url , $prePattern.$this->links->episodeFirst, 0, 'UTF-8')) {
    						return $this->baseRank*2;
    					}else {
    						preg_match('/('.$prePattern.'[?<digit>\d+]*)/', $url, $matches);
    						$dd = explode('-',$matches[0]);
    						$lastnum = intval($dd[0]);
    						if($lastnum > $this->links->episodeLast) {
    							return $this->baseRank*2;
    						}
    						if($lastnum > $this->links->episodeFirst) {
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