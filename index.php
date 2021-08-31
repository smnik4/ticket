<?php
	require "assets/config.php";
	$local_lib = __DIR__ .'/lib.php';
	if(file_exists($local_lib)){
		require $local_lib;
	}
	$action = filter_input(INPUT_GET,"action");
	$page = filter_input(INPUT_GET,"page");
    if(!$menu->page_auth AND (!empty($page) OR !empty($action))){
        //$theme->error("У Вас нет доступа к этой странице");
        //$theme->create();
        ob_clean();
        header("Location: /");
        exit();
    }
    if($USER->id == 0){
        $action = 'auth';
        $page = FALSE;
    }else{
        
        $theme->title("Заявки");
        if(!$action){
            $action = 'start';
        }
    }
    if(!empty($page)){
        $file_action = __DIR__ . "/" .$page."/index.php";
    }else{
        $file_action = __DIR__ . "/action/" . $action . ".php";
    }
	if(file_exists($file_action)){
		include($file_action);
	}else{
        if(!empty($page)){
            echo '<div class="error">Страница не найдена!</div>';
        }else{
            echo '<div class="error">Команда не найдена!</div>';
        }
	}
	$theme->create();