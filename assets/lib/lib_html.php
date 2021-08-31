<?php

//Функции для вывода элементов

function ereg($a,$b){
    $a = addcslashes($a, "/");
    return preg_match("/".$a."/ui", $b);
}

function ifisset($data,$element,$default = NULL){
    if(is_object($data)){
        $data = (array)$data;
    }
    if(isset($data[$element])){
        return $data[$element];
    }else{
        return $default;
    }
}

function clear_text($text){
    $text = str_replace("'", "", $text);
    $text = str_replace('"', "", $text);
    $text = str_replace('\\', "", $text);
    $text = strip_tags($text);
    $text = preg_replace("/\s+/", " ", $text);
    return $text;
}

function attr_to_str($attr){
    if(is_array($attr)){
        foreach($attr as $key=>$val){
            if($val === FALSE){
                continue;
            }
            $attr[$key] = sprintf('%s="%s"',$key,$val);
        }
        $attr = implode(" ",$attr);
    }else{
        $attr = '';
    }
    return $attr;
}

function transform_in($text){
    //подмена всех М на большую русскую
    $text = str_replace(array("M","m","м","v","V","ь","Ь"), "М", $text);
    $text = mb_ereg_replace("[^\dМ]","",$text);
    return $text;
}

function search_in($text){
    //поиск инвентарника
    global $DB;
    if(mb_strlen($text,"UTF-8") <= 2 OR $text == "-"){
        return array();
    }
    $text = transform_in($text);
    $sql = sprintf("SELECT * FROM `hosts_inventory` WHERE `number` LIKE '%%%s%%' "
            . "ORDER BY `number`,`name`  LIMIT 10",$text);
    $sel = $DB -> prepare($sql);
    $sel -> execute();
    return $sel -> fetchAll();
}

function get_in($id){
    //поиск инвентарника
    global $DB;
    $sel = $DB -> prepare("SELECT * FROM `hosts_inventory` WHERE `id` =:id ORDER BY `number`,`name`  LIMIT 10");
    $sel -> execute(array('id'=>$id));
    if($sel -> rowCount() > 0){
        return $sel -> fetch();
    }else{
        return FALSE;
    }
}

function show_in($data){
    $html = '';
    $res = array();
    $res[] = array('Номер',         (isset($data['number']))?       $data['number']:'Ошибка');
    $res[] = array('Статус',        (isset($data['state']))?        $data['state']:'Ошибка');
    $res[] = array('Наименование',  (isset($data['name']))?         $data['name']:'Ошибка');
    $res[] = array('Описание',      (isset($data['description']))?  $data['description']:'Ошибка');
    $res[] = array('МОЛ',           (isset($data['mol']))?          $data['mol']:'Ошибка');
    $res[] = array('Подразделение', (isset($data['div']))?          $data['div']:'Ошибка');
    $res[] = array('Количество',    (isset($data['count']))?        $data['count']:'Ошибка');
    $res[] = array('Поставлен',     (isset($data['date_enter']))?   $data['date_enter']:'Ошибка');
    $res[] = array('Стоимость',     (isset($data['cost']))?         $data['cost']:'Ошибка');
    $res[] = array('Счет',          (isset($data['score']))?        $data['score']:'Ошибка');
    $html .= table($res,array(
        'border'=>1,
        'cellspacing'=>0,
        'cellpadding'=>3,
        ));
    return $html;
}

function show_text($full,$small){
    return sprintf('<span class="show_full">%s</span>'
                  .'<span class="show_small">%s</span>',
            $full,$small);
}

function listing($array){
    $html = '';
    if(is_array($array)){
        foreach($array as $line){
            $l = $c = 'error';
            if(is_array($line)){
                $l = array_shift($line);
                $c = array_pop($line);
            }
            $html .= sprintf('<div>'
                    . '<span class="label">%s</span>'
                    . '<span class="content">%s</span>'
                    . '</div>',
                    $l,$c);
        }
    }
    return $html;
}

function table($array,$attr = NULL){
    $html = '';
    $attr = attr_to_str($attr);
    if(count($array)> 0){
        $html = sprintf('<table %s>',$attr);
        foreach ($array as $rid=>$cols){
            $row_attr = '';
            if(isset($cols['attr'])){
                if(is_array($cols['attr'])){
                    $row_attr = attr_to_str($cols['attr']);
                }else{
                    $row_attr = $cols['attr'];
                }
                unset($cols['attr']);
            }
            $html .= sprintf('<tr %s>',$row_attr);
            if(is_array($cols)){
                foreach($cols as $cid=>$col){
                    $col_attr = '';
                    $value = 'Не задан параметр value';
                    if(is_array($col)){
                        if(isset($col['attr'])){
                            $col_attr = attr_to_str($col['attr']);
                        }
                        if(isset($col['value'])){
                            $value = $col['value'];
                        }
                    }else{
                        $value = $col;
                    }
                    $html .= sprintf('<td %s>%s</td>',$col_attr,$value);
                }
            }else{
                $html .= '<td>Нет ячеек в строке</td>';
                set_error("Нет ячеек в строке ".$rid);
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
    }else{
        set_error("Нет строк для построения таблицы");
    }
    return $html;
}

function print_select($id,$array,$select,$param='',$default=NULL) {
    if(is_array($param)){
        $param = attr_to_str($param);
    }
	$content = '<select id="' . $id . '" name="' . $id . '" '. $param .'>';
        if (!is_null($default)) $content .= '<option value="0">' . $default . '</option>"';
        foreach ($array as $key => $value) {
    	    $content .= sprintf('<option value="%u" %s>%s</option>',$key,($key==$select?'selected':''),$value);
        }
        $content .= '</select>';
        return $content;
}
	
function get_select($array,$name,$selectt = 0,$attr = '',$arr_field = 'name',$default=NULL){
	//получение select
	$html = '<select name="'.$name.'" name="'.$name.'" '.$attr.'>';
        if (!is_null($default)){
            $html .= sprintf('<option value="NULL">%s</option>',$default);
        }
	foreach($array as $key => $val){
        if(!is_string($val)){
            $val = (array)$val;
            if(isset($val['id'])){
                $key = $val['id'];
            }
            if(isset($val[$arr_field])){
                $val = $val[$arr_field];
            }else{
                $val = 'field not found';
            }
        }
		$select = ((string)$selectt === (string)$key)?" selected":"";
		$html .= sprintf('<option value="%s"%s>%s</option>',$key,$select,$val);
	}
	$html .= '</select>';
	return $html;
}

function time_show($dt){
    //перевод unixtime в текстовое представление
    $sec = time()-$dt;
    if ($sec<5) {
        return 'только что';
    }elseif($sec<60) {
        return $sec." сек. назад";
    }elseif($sec<60*60) {
        return floor($sec/60). ' мин. назад';
    }elseif($sec<60*60*4) {
        return floor($sec/3600). ' час. назад';
    }elseif($sec<60*60*date('G')) {
        return 'сегодня в ' . date('H:i',$dt);
    }elseif($sec<60*60*(date('G')+24)) {
	return 'вчера в ' . date('H:i',$dt);
    }else{
	return date("d.m.Y, H:i",$dt);
    }
}

function time_show_event($dt){
    //перевод unixtime в текстовое представление
    $sec = time()-$dt;
    if($sec > 0){
        return 'Уже пора делать';
    }
    $sec = $sec * (-1);
    if ($sec<5) {
        return 'Осталось очень мало';
    }elseif($sec<60) {
        return "Осталось ".$sec." сек.";
    }elseif($sec<60*60) {
        return "Осталось ".floor($sec/60). ' мин.';
    }elseif($sec<60*60*4) {
        return "Осталось ".floor($sec/3600). ' час.';
    }elseif($sec<60*60*date('G')) {
        return 'Сегодня в ' . date('H:i',$dt);
    }elseif($sec<60*60*(date('G')+24)) {
	return 'Завтра в ' . date('H:i',$dt);
    }else{
	return date("d.m.Y, H:i",$dt);
    }
}

function get_big_btn($array,$link,$addon_text_start = '',$addon_text_end = ''){
	//построение кнопок
	$html = '';
	foreach ($array as $key => $value) {
		$sub_link = $link.$key;
		$html .= sprintf('<a class="btn_big" href="%s">%s%s%s</a>',$sub_link,$addon_text_start,$value,$addon_text_end);
    }
	return $html;
}

//работа с данными
function collapse_array($arr1,$arr2){
	//объединение массивов с индексами
	if(count($arr2) == 0 OR $arr2 == FALSE){
		return $arr1;
	}
	foreach($arr2 as $key=>$val){
		$arr1[$key] = $val;
	}
	return $arr1;
}
/*
//сортировка многомерного массива по полю
$sort_custon_field = NULL;
$sort_custon_type = NULL;
function sort_m($array,$field,$type = "ASC"){
    global $sort_custon_field,$sort_custon_type;
    $sort_custon_field = $field;
    $sort_custon_type = $type;
    usort($array,"sort_func");
    return $array;
}
function sort_func($a,$b){
    global $sort_custon_field,$sort_custon_type;
    if(isset($a[$sort_custon_field]) AND isset($b[$sort_custon_field])){
        $a = $a[$sort_custon_field];
        $b = $b[$sort_custon_field];
        debug("field ".$a." - ".$b);
        if ($a == $b) {
            return 0;
        }
        if($sort_custon_type == "ASC"){
            return ($a > $b) ? -1 : 1;
        }else{
            return ($a > $b) ? 1 : -1;
        }
    }else{
        return 0;
    }
}*/

function get_sql_array($table,$field = "name",$condition = FALSE,$group = FALSE,$order = FALSE){
	//получение данных
	global $DB;
	$arr = array();
	if($condition){
		$condition = 'WHERE '.$condition;
	}else{
		$condition = '';
	}
	if($group){
		$group = 'GROUP BY `'.$group.'`';
	}else{
		$group = '';
	}
	if(!$order AND $field != "*"){
		$order = $field;
	}elseif(is_array($order)){
		$order = implode(", ",$order);
	}elseif($field == "*"){
		$order = '`id`';
	}
	if($field !== "*"){
		$sel_field = "`id`,".$field;
	}else{
		$sel_field =  $field;
	}
	$sql = sprintf("SELECT %s FROM `%s` %s %s ORDER BY %s",$sel_field,$table,$condition,$group,$order);
	$sel = $DB -> prepare($sql);
	$sel -> execute();
	if($sel){
		if($sel -> rowCount() == 0){
			return FALSE;
		}
		if($field === "*"){
			$arr = $sel -> fetchAll();
		}else{
			while($row = $sel -> fetch()){
				$arr[$row['id']] = $row[$field];
			}
		}
		return $arr;
	}else{
		return FALSE;
	}
}

function set_search_block($text,$search,$color = "#FFF452"){
	//выделяет искомый текст цветом
	$search = str_replace("%","",$search);
	$replase = sprintf('<span style="background-color:%s;">%s</span>',$color,$search);
	//return $replase;
	return str_replace($search,$replase,$text);
}

function view_errors(){
    //вывод ошибок
    global $errors;
    if(count($errors) > 0){
	$html = '<div class="'.$class.'"><ul>';
	foreach($array as $val){
            $html .= sprintf('<li>%s</li>',$val);
	}
	$html .= '</ul></div>';
	echo $html;
    }
}

function view_messages($is_html = TRUE){
    //вывод всех типов сообщений
    global $errors,$messages,$infos,$helpes;
    $res = array();
    $GB = array(
        'error'=>$errors,
        'message'=>$messages,
        'info'=>$infos,
        'help'=>$helpes,
    );
    foreach($GB as $type=>$vars){
        if(count($vars) > 0){
            foreach($vars as $var){
                if(is_string($var)){
                    $res[] = sprintf('<div class="%s">%s</div>',$type,$var);
                }elseif(is_array($var)){
                    if(isset($var['value'])){
                        $noclose = FALSE;
                        $id = '';
                        if(isset($var['noclose'])){
                            $noclose = $var['noclose'];
                        }
                        if(isset($var['id'])){
                            $id = $var['id'];
                        }
                        $res[] = sprintf('<div class="%s%s"%s>%s</div>',
                                $type,
                                ($noclose)?" ".$var['noclose']:"",
                                (!empty($id))? sprintf(' id="%s"',$id):"",
                                $var['value']);
                    }
                }
            }
        }
    }
    if($is_html){
        return implode("",$res);
    }else{
        return $res;
    }
}

function text_to_link($text){
	$text = in_link_link($text);
	$text = in_link_inventory($text,TRUE);
	$text = in_link_mac($text,TRUE);
	$text = in_link_ip($text);
	return $text;
}

function in_link_link($text){
    //делаем урлы в тексте ссылками
    if(mb_strlen($text) > 80){
        return preg_replace('/((ht|f)tp(s?)\:\/\/[0-9a-zA-Z]([-.\w]*[0-9a-zA-Z])*(:(0-9)*)*(\/?)([a-zA-Z0-9\-\.\?\=\,\'\/\\\+&%\$#_]*)?[^\s]*)/','<a href="$1" target="_blank">Ссылка</a>',$text);
    }else{
        return preg_replace('/((ht|f)tp(s?)\:\/\/[0-9a-zA-Z]([-.\w]*[0-9a-zA-Z])*(:(0-9)*)*(\/?)([a-zA-Z0-9\-\.\?\=\,\'\/\\\+&%\$#_]*)?[^\s]*)/','<a href="$1" target="_blank">$1</a>',$text);
    }
}

function in_link_inventory($text,$blank = FALSE){
    //делаем инвентарники ссылками на базу мол
    global $DB,$CONFIG;
    $preurl = '';
    if(isset($CONFIG['PATH']['www']['paths'])){
        $preurl = $CONFIG['PATH']['www']['paths'];
    }
    //$text = str_replace("М","M",$text);
    //$text = str_replace("м","M",$text);
    $reg = "(([МмMm]{1}0\d{5,11})|(00013\d+)|(0318\d+))";
    $regs = array(
        "(M0\d{2,11})",
        "(00013\d+)",
        "(0318\d+)",
    );
    $d = mb_ereg_match_all($reg,$text);
    $replace = array();
    if(is_array($d)){
        if(count($d) > 0){
            foreach($d as $f){
                foreach($f as $r){
                    $r = trim($r);
                    if(empty($r)){
                        continue;
                    }
                    if(!in_array($r,$replace)){
                        $replace[] = $r;
                    }
                }
            }
        }
    }
    if(count($replace) > 0){
        foreach($replace as $f){
            $fs = str_replace(array("м","M","m"),"М",$f);
            /*$sql = sprintf("SELECT * FROM `hosts_inventory` WHERE `number` LIKE '%s'",
				$fs);
            $sel = $DB -> prepare($sql);
            $sel -> execute();
            $title = "ИН не найден";
            $add_text = array();
            if($sel -> rowCount() > 1){
                $nn = 0;
                while($inv = $sel -> fetch()){
                    $nn++;
                    $add_text[] = sprintf("<span title=\"%s\n\n%s\n%s\">[%s]</span>",
                            htmlspecialchars($inv['name']),
                            htmlspecialchars($inv['mol']),
                            htmlspecialchars($inv['div']),
                            $nn);
                }
                $title = "С таким ИН несколько позиций";
            }elseif($sel -> rowCount() == 1){
                $data = $sel -> fetch();
                $title = sprintf("%s\n\n%s\n%s",	
                    htmlspecialchars($data['name']),
                    htmlspecialchars($data['mol']),
                    htmlspecialchars($data['div']));
            }
            $r = sprintf('<a href="%s/mol/?action=base&q=%s"%s title="%s">%s</a>%s',
                    $preurl,
                    $f,
                    ($blank)?' target="blank"':'',
                    $title,
                    $f,
                    implode(" ",$add_text));*/
            $r2 = Ticket::get_inventory_links($fs);
            //debug($r2);
            $text = mb_ereg_replace($f,$r2,$text);
        }
    }
    return $text;
}

function in_link_mac($text,$blank = FALSE){
    global $CONFIG;
    $preurl = '';
    if(isset($CONFIG['PATH']['www']['paths'])){
        $preurl = $CONFIG['PATH']['www']['paths'];
    }
    if(preg_match("/(\s|^)(([\w0123456789]{2}[:-]{1}){5}[\w0123456789]{2})(\s|$)/",$text,$f)){
        if(isset($f[2])){
            $f = $f[2];
            $fr = str_replace("-",":",$f);
            $tr = sprintf('<a href="%s/hosts/?mac=%s&find_switch=1"%s>%s</a>',
                    $preurl,
                    urlencode($fr),
                    ($blank)?' target="blank"':'',
                    $fr);
            $text = str_replace($f,$tr,$text);
        }
    }
    return $text;
}
function in_link_ip($text){
    global $CONFIG;
    $preurl = '';
    if(isset($CONFIG['PATH']['www']['paths'])){
        $preurl = $CONFIG['PATH']['www']['paths'];
    }
    if(preg_match("/([0123456789]{1,3}\.){3}[0123456789]{1,3}/",$text,$f)){
        if(isset($f[0])){
            $f = $f[0];
            $tr = sprintf('<a href="%s/hosts/?ip=%s">%s</a> <a href="http://%s" target="_blank"><img src="/img/link_url.png" alt="W" title="Открыть веб страницу"></a>',
                    $preurl,
                    urlencode($f),
                    $f,
                    $f);
            $text = str_replace($f,$tr,$text);
        }
    }
    return $text;
}

function mb_ereg_match_all($pattern, $subject){
    if (!mb_ereg_search_init($subject, $pattern)) {
        return false;
    }
    $subpatterns = array();
    while ($r = mb_ereg_search_regs()) {
        $subpatterns[] = $r;
    }
    return $subpatterns;
}