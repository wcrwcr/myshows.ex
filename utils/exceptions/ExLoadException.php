<?php 
class ExLoadException extends Exception
{
    var $url; 
    
    public function __construct($message, $url = '', Exception $previous = null) {
        $this->url = $url;
        parent::__construct($message, 0, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": {$this->message} : [{$this->url}]\n";
    }

}