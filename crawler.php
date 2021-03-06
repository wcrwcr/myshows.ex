<?php
define ('DATE',date("d.m.y.H.i.s"));
define('LOG', true);
//define('LOG', false);

require_once 'vendors/Loader.php';
require_once 'utils/Loader.php';

define ('LOGPATH', $SETTINGS['logPath']);

if (LOG) {
    ob_start();
}

function setView($seasonEpisode, $data, $ms) {
	//$data['crawler']['searcher']['rawData']['episodes']
	$tmp = explode('-', $seasonEpisode);
	$season = intval($tmp[0]);
	$episode = intval($tmp[1]);
	foreach ($data['crawler']['searcher']->rawData['episodes'] as $item) {
		if ($season == $item->seasonNumber && $episode == $item->episodeNumber) {
			$ms->CheckEpisode($item->episodeId);
		}
	}
	
}

$ms = new MyShowsClient ($SETTINGS['login'], $SETTINGS['password']);

$ms->login();

$need2Load = false;
$list = array();

foreach ($ms->GetMyShows() as $item) {
    $list[$item->showId] = array(
        'title'=>$item->title,
        'ruTitle'=>$item->ruTitle
    );
}
//сортируем по шоу
foreach ($ms->GetUnwatchedEpisodes() as $item) {
    $need2Load = true;
    $list[$item->showId]['episodes'][] = $item;
}

//чистим без серий
//фильтруем по дате
foreach ($list as $key=>$item) {
    if(!isset($item['episodes'])) {
        unset($list[$key]);
    } else {
        foreach($item['episodes'] as $eKey=>$eItem) {
            if(strtotime($eItem->airDate) + $SETTINGS['translateLag'] *24*60*60 > time()) {
                unset($item['episodes'][$eKey]);
            }
        }
        if (empty($item['episodes'])) {
            unset($list[$key]);
        }
    } 
}


$results = array();
echo PHP_EOL.PHP_EOL.PHP_EOL."got serials searching.";
foreach ($list as $key => &$item) {
    $results[$key]['crawler']['searcher'] = new searcher($item);

    	foreach (array(true, false) as $series) {
    		foreach (array(true, false) as $ru) {
    		    if ($series) {
        			foreach (array(true, false) as $last) {
        				echo '.';
        				$params = array($ru, $last, $series);
        				$found = call_user_func_array(array($results[$key]['crawler']['searcher'], 'search'), $params);
        				
        				if (!is_null($found)) {
        				    dumpIncremental("got urls".var_export($found, 1), '_#ranklogPromoted.log');
        				    
        					break;
        				}
        				
        			}
    		    } else {
    		        $last = 0;
    		        echo '.';
    		        $params = array($ru, $last, $series);
    		        $found = call_user_func_array(array($results[$key]['crawler']['searcher'], 'search'), $params);
    		        if (!is_null($found)) {
    		            dumpIncremental("got urls".var_export($found, 1), '_#ranklogPromoted.log');
    		            
    		            break;
    		        }
    		    }
    		}
    	}
    
    $results[$key]['crawler']['results'] = $found;
}

$found = 0;
$links = array();
$notLinks = array();
  
foreach ($results as &$item) {
	$item['links'] = array();
	if (isset($item['crawler']['results']['href'])) {
		$o = new linkRetriever($SETTINGS['getString'].$item['crawler']['results']['href']);
		$lRet = $o->stripLinks($item['crawler']['searcher']);
		if (empty($lRet)) {
			$notLinks[] = $SETTINGS['getString'].$item['crawler']['results']['href'];
		}
		$item['links'] = array_merge($item['links'], $lRet);
		$links = array_merge($links, $lRet);
		$found ++;
	}
}
echo PHP_EOL."total serials: ".count($results). " found: {$found}".PHP_EOL;
echo PHP_EOL."retrieving.".PHP_EOL;

if (!empty ($links)) {
    $downloader = new downloader($SETTINGS);
	foreach($results as $item) {
		if (!empty($item['links'])){
			foreach ($item['links'] as $seasonEpisode => $link){
				$downloader->get($link);
				setView($seasonEpisode, $item, $ms);
				cliOut("{$item['crawler']['searcher']->rawData['ruTitle']} {$seasonEpisode} set as viewed.");
			}
				
		}
	}
	cliOut("bg retriever started, wait some time.");
} else {
	cliOut("nothing to retrieve.");
}


if (!empty($notLinks)) {
	dump(implode(PHP_EOL, $notLinks), '_#didntRetrieved.log');	
	echo PHP_EOL."some of the serials was found but cant retrieve links, check _#didntRetrieved.log.".PHP_EOL;
}

if (LOG) {
    $log = ob_get_contents();
    ob_end_clean();
    dump($log, '.run'.'.log');
}

//echo pr($es);
dump($results, '.debug'.'.log');
