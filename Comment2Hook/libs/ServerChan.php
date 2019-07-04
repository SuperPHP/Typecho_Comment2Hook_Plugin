<?php

class ServerChan {
    private $url;
    private $text;
    private $desp;

    function __construct($url, $text, $desp) {
       $this->url = $url;
       $this->text= $text;
       $this->desp = $desp;
    }

    public function trigger(){
        $postdata = http_build_query(
            array(
                'text' => $this->text,
                'desp' => $this->desp
            )
        );
        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        $context  = stream_context_create($opts);
        file_get_contents($this->url, false, $context);
    }
}

?>