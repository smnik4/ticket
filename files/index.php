<?php

require "../assets/config.php";

if($USER->id == 0){
    header('HTTP/1.0 403 Forbidden');
    $theme->title("Нет доступа!");
    $theme->create();
    exit();
}
$id = filter_input(INPUT_GET, "file",FILTER_VALIDATE_INT);
$file = new File(0,0);
if($file->load($id)){
    //файл есть
    $file->download();
}else{
    //файла нет
    header("HTTP/1.0 404 Not Found");
    $theme->title("ФАЙЛ НЕ НАЙДЕН ИЛИ УДАЛЕН");
    $theme->create();
}

