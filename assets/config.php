<?php

//ini_set('display_errors', 1);
error_reporting(E_ALL);

ini_set('date.timezone', 'Asia/Almaty');
ob_start();
$DEBUG = $errors = $infos = $messages = $helpes = $err_fiedls = array();

$ini_file = __DIR__ . "/config.ini";
if (file_exists($ini_file)) {
    $CONFIG = parse_ini_file($ini_file, true);
    if ($CONFIG == FALSE) {
        exit("INVALID config.ini");
    } else {
        $CONFIG['version'] = '1.4';
        $CONFIG['server'] = (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'ticket.smnik.ru';
        $session_path = dirname($_SERVER['DOCUMENT_ROOT'])."/tmp/";
        if(isset($CONFIG['SESSION']['path'])){
            if(file_exists($CONFIG['SESSION']['path'])){
                $session_path = $CONFIG['SESSION']['path'];
            }
        }
        $session_live = 60*60*24;
        if(isset($CONFIG['SESSION']['live'])){
            $CONFIG['SESSION']['live'] = intval($CONFIG['SESSION']['live']);
            if($CONFIG['SESSION']['live'] > 0){
                $session_live = $CONFIG['SESSION']['live'];
            }
        }
        if (file_exists($session_path)) {
            if(is_writable($session_path)){
                session_save_path($session_path);
                //debug(ini_set('session.save_path',$session_path));
            }
            
            /* Clear old seccion */
            $files = scandir($session_path);
            if ($files) {
                foreach ($files as $file) {
                    $full_file_path = $session_path . $file;
                    if (preg_match("/^sess_/", $file) AND filemtime($full_file_path) < (time() - $session_live)) {
                        unlink($full_file_path);
                    }
                }
            }
        }
        ini_set('session.cookie_domain', $CONFIG['server']);
        $ishttps = TRUE;
        if(isset($CONFIG['SESSION']['https'])){
            $ishttps = boolval($CONFIG['SESSION']['https']);
        }
        if($ishttps){
            ini_set('session.cookie_httponly', 'On');
            ini_set('session.cookie_secure', 'On');
        }
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', $session_live);
        ini_set('session.cookie_lifetime', $session_live);
        //debug(ini_get('session.gc_maxlifetime'));
        //debug($session_path);
        //debug(ini_get('session.save_path'));
        session_start();
        //phpinfo();
        //exit();
        
        if (isset($CONFIG['PATH'])) {
            $path = $CONFIG['PATH'];
            $CONFIG['PATH']['CFG'] = $path;
            $CONFIG['PATH']['CFG']['doc_root'] = $_SERVER['DOCUMENT_ROOT'];
            $CONFIG['PATH']['cache'] = array();
            $CONFIG['PATH']['base']['path'] = dirname(__DIR__);
            $CONFIG['PATH']['www']['paths'] = str_replace($_SERVER['DOCUMENT_ROOT'], "", $CONFIG['PATH']['base']['path']);
            $CONFIG['PATH']['www']['path'] = str_replace($_SERVER['DOCUMENT_ROOT'], "", $CONFIG['PATH']['base']['path']) . "/";
            foreach ($path as $key => $dir) {
                $dir = $CONFIG['PATH']['base']['path'] . $dir;
                $CONFIG['PATH']['base'][$key] = array();
                $CONFIG['PATH']['www'][$key] = array();
                if (preg_match("/css|cache/", $key)) {
                    //проверяем права записи кэша и css
                    if (!is_writable($dir)) {
                        if (!chown($dir, "www-data") OR!chgrp($dir, "www-data")) {
                            exit("could not change owner: " . $dir);
                        } else {
                            if (!chmod($dir, "0755")) {
                                exit("could not change write permission: " . $dir);
                            }
                        }
                        exit("NOT WRITE TO: " . $dir);
                    }
                }
                if (file_exists($dir)) {
                    $dir_content = scandir($dir);
                    unset($dir_content[0]);
                    unset($dir_content[1]);
                    foreach ($dir_content as $file_name) {
                        $file = $dir . "/" . $file_name;
                        switch ($key) {
                            case 'libdir':
                                //добавляем все либы к исполнению
                                if (preg_match("/.*\.php$/", $file)) {
                                    if (preg_match("/^\/.*/", $file_name)) {
                                        $CONFIG['PATH']['base'][$key][] = $file_name;
                                        require $file_name;
                                    } else {
                                        $CONFIG['PATH']['base'][$key][] = $file;
                                        require $file;
                                    }
                                }
                                break;
                            case 'cssdir':
                            case 'jsdir':
                            case 'cachedir':
                                //Сканим директории и для сверки кэша
                                if (preg_match("/css/", $key)) {
                                    $c_cache = $CONFIG['PATH']['base']['path'] .
                                            $CONFIG['PATH']['CFG']['cachedir'] .
                                            "/" .
                                            md5_file($file) . ".css";
                                    $CONFIG['PATH']['cache'][] = $c_cache;
                                    if (!file_exists($c_cache)) {
                                        copy($file, $c_cache);
                                    }
                                }
                                if (preg_match("/js/", $key)) {
                                    $c_cache = $CONFIG['PATH']['base']['path'] .
                                            $CONFIG['PATH']['CFG']['cachedir'] .
                                            "/" .
                                            md5_file($file) . ".js";
                                    $CONFIG['PATH']['cache'][count($CONFIG['PATH']['cache'])] = $c_cache;
                                    if (!file_exists($c_cache)) {
                                        copy($file, $c_cache);
                                    }
                                }
                                $CONFIG['PATH']['base'][$key][] = $file;
                                if (!preg_match("/cache/", $key)) {
                                    $CONFIG['PATH']['www'][$key][] = str_replace($_SERVER['DOCUMENT_ROOT'], "", $file);
                                }
                                break;
                            case 'imagedir':
                                //Сканим директории картинок для замены 
                                $CONFIG['PATH']['base'][$key][] = $file_name;
                                $CONFIG['PATH']['www'][$key][] = str_replace($_SERVER['DOCUMENT_ROOT'], "", $file);
                                break;
                        }
                    }
                } else {
                    exit("PATH NOT FIND: " . $dir);
                }
            }
            //проверяем и пишем кэш CSS и JS при изменении
            if (isset($CONFIG['PATH']['base']['cachedir'])
                    AND isset($CONFIG['PATH']['www']['cachedir'])
                    AND isset($CONFIG['PATH']['cache'])) {
                $DEL_CACHE = array_diff(
                        $CONFIG['PATH']['base']['cachedir'], //что лежит в кэше
                        $CONFIG['PATH']['cache']); //что должно делать в кэше
                foreach ($DEL_CACHE as $del_file) {
                    //удаляем не нужное
                    unlink($del_file);
                }
                foreach ($CONFIG['PATH']['cache'] as $file) {
                    $CONFIG['PATH']['www']['cachedir'][] = str_replace(
                            $CONFIG['PATH']['CFG']['doc_root'], "", $file);
                }
            }
            if (isset($CONFIG['PATH']['base']['cssdir']) AND isset($CONFIG['PATH']['base']['imagedir'])) {
                //обновляем пути до картинок в CSS
                foreach ($CONFIG['PATH']['cache'] as $css) {
                    if (!preg_match("/.*\.css$/", $css)) {
                        continue;
                    }
                    $data = file_get_contents($css);
                    $odata = $data;
                    foreach ($CONFIG['PATH']['base']['imagedir'] as $key => $image) {
                        if (isset($CONFIG['PATH']['www']['imagedir'][$key])) {
                            $reg = "/url\([\/\w\d\-\_\.]*" . $image . "\)/";
                            $rep = "url(" . $CONFIG['PATH']['www']['imagedir'][$key] . ")";
                            $data = preg_replace($reg, $rep, $data);
                        }
                    }
                    if (strcmp($odata, $data)) {
                        //перезаписываем при изменении оригинального файла
                        if (!file_put_contents($css, $data)) {
                            exit("NOT WRITE CSS FILE: " . $css);
                        }
                    }
                }
            }
        } else {
            exit("PATH NOT DEFINED IN config.ini");
        }
    }
} else {
    exit("NOT FOUND config.ini");
}

function debug($data = FALSE) {
    global $DEBUG;
    $text = '';
    //$re_ip = filter_input(INPUT_SERVER, "REMOTE_ADDR", FILTER_VALIDATE_IP);
    /* $debug_ip = array(
      '192.168.4.84',
      //'192.168.4.167',//ДМ
      //'192.168.4.188',//Роман
      );
      if(in_array($re_ip,$debug_ip)){ */
    $debug_info = debug_backtrace()[0];
    $text .= sprintf("\n<b>DEBUG ON: %s:%s</b>\n", $debug_info['file'], $debug_info['line']);
    $text .= htmlspecialchars(var_export($data, TRUE));
    $DEBUG[] = $text;
    //}
}

function set_error($text, $noclose = FALSE, $id = FALSE) {
    global $errors;
    $errors[] = array(
        'value' => (preg_match("/\d/", $text)) ? $text : t($text),
        'noclose' => $noclose,
        'id' => $id,
    );
}

function set_info($text, $noclose = FALSE, $id = FALSE) {
    global $infos;
    $infos[] = array(
        'value' => (preg_match("/\d/", $text)) ? $text : t($text),
        'noclose' => $noclose,
        'id' => $id,
    );
}

function set_message($text, $noclose = FALSE, $id = FALSE) {
    global $messages;
    $messages[] = array(
        'value' => (preg_match("/\d/", $text)) ? $text : t($text),
        'noclose' => $noclose,
        'id' => $id,
    );
}

function set_help($text, $noclose = FALSE, $id = FALSE) {
    global $helpes;
    $helpes[] = array(
        'value' => (preg_match("/\d/", $text)) ? $text : t($text),
        'noclose' => $noclose,
        'id' => $id,
    );
}

function set_error_field($name) {
    global $err_fiedls;
    $err_fiedls[] = $name;
}

function set_param($name, $subname, $value) {
    if (!isset($_SESSION[$name])) {
        $_SESSION[$name] = array();
    }
    $_SESSION[$name][$subname] = htmlspecialchars($value);
}

if (!class_exists("THEME")) {
    exit("THEME CLASS ERROR");
}
if (!class_exists("Menu")) {
    exit("MENU CLASS ERROR");
}
if (!class_exists("User")) {
    exit("USER CLASS ERROR");
}
if (!class_exists("html")) {
    exit("HTML CLASS ERROR");
}
if (!class_exists("AJAX")) {
    exit("AJAX CLASS ERROR");
}
$theme = new THEME();

$verfile = __DIR__ . '/version.inf';
if (file_exists($verfile)) {
    $version = number_format(file_get_contents($verfile), 1, ".", "");
} else {
    $version = '1.0';
}
$CONFIG['version'] = number_format($CONFIG['version'], 1, ".", "");
if ($CONFIG['version'] > $version) {
    require __DIR__ . '/update.php';
    
    while ($CONFIG['version'] > $version) {
        $find = round($version + 0.1 , 2);
        $func = sprintf('update_%s_to_%s', str_replace(".", "_", $version), str_replace(".", "_", $find));
        if (function_exists($func)) {
            $version = round($func(),2);
        } else {
            set_error("Не найдена функция обновления ядра системы", TRUE);
            echo '<div class="error">Не найдена функция обновления ядра системы!</div>';
            $theme->create();
            exit();
        }
        if ($version === $find) {
            if(!file_put_contents($verfile, $version)){
                set_error("Не смог записать информацию в файл: ".$verfile, TRUE);
                echo '<div class="error">Не смог записать информацию в файл:'.$verfile.'</div>';
                $theme->create();
                exit();
            }
        }
    }
    ob_clean();
    header("Location: /");
    exit();
}

$USER = new User();
$DIV = new div($USER->div_id);
if(!empty($DIV->timezone)){
    ini_set('date.timezone', $DIV->timezone);
}
$USERS = $DIV->get_users();
$KORPUS = $DIV->get_korpus();
$KORPUS_SHORT = $DIV->get_korpus_short();
$menu = new Menu($USER->groups);
if ($USER->id > 0) {
    $theme->menu($menu->menu_html);
}
