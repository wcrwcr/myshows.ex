<?php
require_once 'vendors/Loader.php';
require_once 'utils/Loader.php';

$SETTINGS = array(
    'login'=>'wcr',
    'password' => '123321',
    'translateLag' => 4, 
    'searchString' => 'http://www.ex.ua/search?s='
);

$t= new stdClass();
	foreach (array( 
			'season' => 2,
			'episodeFirst' => 4,
			'episodeLast' => 4,
			'year' => 1455753600,
			'name' => 'как избежать наказания за убийство',
			'nameEng' => 'Life in Pieces',
			'urls' => Array(
					0 => Array(
							'href' => '/94138482',
							'text' => 'как избежать наказания за убийство / how to get away with murder (2015) / hdtvrip / ideafilm / сезон 2 (серия 1 - 4) [ru, en]'
					)
			)
	) as $prop => $val) {
		$t->$prop = $val;
}

$r = new ranker($t);
echo $r->rankSeason($t->urls[0]['text']);
//cd /data