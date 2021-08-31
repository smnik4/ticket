<?php

$theme->title(t("Утилиты"));
$file_action = __DIR__ . "/action/" . $action . ".php";
if(file_exists($file_action)){
	include($file_action);
}else{
    echo '<div class="error">Команда не найдена!</div>';
}