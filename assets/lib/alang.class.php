<?php

class lang_t{
    private $lang_search = array();
    private $lang_replace = array();
    private $lans_file = '';
    private $err = FALSE;
    
    public function __construct() {
        $this->lans_file = dirname(__DIR__).'/lang.ini';
        if(file_exists($this->lans_file)){
            $pf = parse_ini_file($this->lans_file);
            if($pf !== FALSE){
                $this->lang_replace = $this->lang_search = (array)$pf;
            }else{
                $this->err = TRUE;
                global $errors;
                $errors[] = array(
                    'value' => 'Ошибка разбора файла трансляций',
                    'noclose' => false,
                    'id' => 'errlng',
                );
            }
        }
    }
    
    public function __destruct() {
        if(!$this->err AND count($this->lang_replace) > count($this->lang_search)){
            $w = [];
            //debug($this->lang_replace);
            foreach($this->lang_replace as $k=>$v){
                $k = trim($k);
                $v = trim($v);
                if(empty($k) OR empty($v)){
                    continue;
                }
                $w[] = $k.' = '.$v;
            }
            $w = implode("\n",$w);
            file_put_contents($this->lans_file, $w);
        }
    }
    
    public function t($text) {
        $text = trim($text);
        if(!is_string($text) OR preg_match("/^\d*$/ui", $text) OR preg_match("/\!/ui", $text) OR preg_match("/\=/ui", $text)){
            global $errors;
                $errors[] = array(
                    'value' => 'Ошибка транслирования: '. print_r($text, TRUE),
                    'noclose' => false,
                    'id' => 'errlng',
                );
            return $text;
        }
        if(!isset($this->lang_replace[$text])){
            $this->lang_replace[$text] = $text;
        }
        return strtr($text,$this->lang_replace);
    }
}

$lng_tt = new lang_t();

function t($text) {
    global $lng_tt;
    if($text == 'Организация2'){
        debug(debug_backtrace());
    }
    if(preg_match("/\(|\)/", $text)){
        return $text;
    }
    return $lng_tt->t($text);
}
