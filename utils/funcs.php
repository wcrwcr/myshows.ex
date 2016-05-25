<?php
function pr($i) {
    if(is_array($i) || is_object($i)) {
        return print_r($i, 1);
    }
    else {
        return var_export($i, 1);
    }
}

function dump($i, $file='__test') {
    $file = LOGPATH.DATE.$file;
    file_put_contents($file, pr($i));
}

function dumpIncremental($i, $file='__test') {
    $file = LOGPATH.DATE.$file;
	$str = file_get_contents($file);
	$str .= PHP_EOL.$i; 
	file_put_contents($file, $str);
}

function cliOut($msg) {
	echo $msg.PHP_EOL;
}
function execInBackground($cmd) {
	if (substr(php_uname(), 0, 7) == "Windows"){
		pclose(popen("start /B ". $cmd, "r"));
	}
	else {
		exec($cmd . " > /dev/null &");
	}
}

function overloadErrors ($enable = true, $excName = 'Exception') {
    //back to default handler
    restore_error_handler();
    if ($enable) {
        set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
            // error was suppressed with the @-operator
            if (0 === error_reporting()) {
                return false;
            }
        
            throw new $excName ($errstr, 0, $errno, $errfile, $errline);
        });
    }
}

if (!function_exists("mb_str_replace"))
{
    function mb_str_replace($needle, $replacement, $haystack) {
        return implode($replacement, mb_split($needle, $haystack));
    }
    
}

function stripSS($text) {

    //marvel's || dc's - this kind of shit
    $textStriped = explode(' ', $text);
    if (mb_stripos($textStriped[0], "'", 0, 'UTF-8') !== false) {
        unset($textStriped[0]);
    }

    $text = implode(' ', $textStriped);

    //Агенты «Щ.И.Т.»
    $text = mb_str_replace("»", "", $text);
    $text = mb_str_replace("«", "", $text);

    //real o'neals
    $text = mb_str_replace("’", "", $text);
    
    return $text;
}

function decodeSpecials($input) {
    return html_entity_decode(preg_replace_callback("/(&#[0-9]+;)/", function($m) { return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES"); }, $input));
}