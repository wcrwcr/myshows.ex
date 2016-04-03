<?php
class downloader {

    private $type;
    private $options;
    
    public function __construct($options) {
        $this->type = $options['storage'];
        $this->options = $options;
        return $this;
    }
    
    public function get($url) {
        cliOut("retrieving {$url}");
        $transport = new $this->type ($url, $this->options);
        execInBackground($transport->command($url));
        
        return $this; 
    }
    
}

class wget {
    
    var $options;
    
    public function __construct($url, $options) {
        $this->options = $options;
        return $this;
    }
    
    public function command($link) {
        $command = "{$this->options['wgetPath']}wget {$this->options['getString']}{$link} -P{$this->options['storagePath']} -b";
        return $command;
    }
}

class dmaster {

    var $options;

    public function __construct($url, $options) {
        $this->options = $options;
        return $this;
    }

    public function command($link) {
        
        $command = "{$this->options['dmasterPath']}dmaster.exe {$this->options['getString']}{$link} hidden=1 start=1";
        dumpIncremental($command, $this->options['logPath'].DATE.'_linksDebug.log');
        sleep(5);
        return $command;
    }
}

/*
 * retrieving.
http://ex.ua/get/229898717
http://ex.ua/get/229562937
http://ex.ua/get/229686581

 */