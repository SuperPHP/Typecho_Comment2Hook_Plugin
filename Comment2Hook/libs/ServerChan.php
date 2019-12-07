<?php

require __DIR__ . '/dotenv/Exception/ExceptionInterface.php';
require __DIR__ . '/dotenv/Exception/FormatException.php';
require __DIR__ . '/dotenv/Exception/FormatExceptionContext.php';
require __DIR__ . '/dotenv/Exception/PathException.php';

require __DIR__ . '/dotenv/Dotenv.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

class ServerChan {
    private $url;
    private $text;
    private $desp;

    function __construct($url = NULL, $text = NULL, $desp = NULL) {
       $this->url = $url;
       $this->text= $text;
       $this->desp = $desp;
    }

    public function enqueue($job, $payload){
        // var_dump($_ENV);
        // return;
        //连接专门的queue数据库，而不是使用$db = Typecho_Db::get();
        try{
            $dsn = $_ENV["DB_TYPE"] . ":host=" . $_ENV["DB_HOST"] .";dbname=" .$_ENV["DB_NAME"];
            $dbConn = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
            $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "INSERT INTO sdq_jobs (channel, job, is_done, payload, created_at,done_at) VALUES (?,?,?,?,?,?)";
            $stmt = $dbConn->prepare($sql,array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $affected_rows = $stmt->execute(["blog",$job, 0, $payload,time(), NULL]);
            if ($affected_rows  != 1){
                return -1;
            }
        }catch(PDOException $e){
            return "ERROR: " . $e->getMessage();
        }
        return 1;
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