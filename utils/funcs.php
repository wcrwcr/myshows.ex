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