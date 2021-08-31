<?php
require "../assets/config.php";
$botname = false;
if(count($_GET) > 0){
    $get = array_keys($_GET);
    $botname = array_shift($get);
}
if($botname){
    $bot = new tlgbot($botname);
}else{
    header('HTTP/1.0 404 Not Found', true, 404);
    exit(1);
};