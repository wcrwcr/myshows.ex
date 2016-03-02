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
    file_put_contents($file, pr($i));
}

function dumpIncremental($i, $file='__test') {
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