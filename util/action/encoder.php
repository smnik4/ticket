<?php
$theme->title(t("En/Decoder"));
$data = filter_input(INPUT_POST, 'data');
$type = filter_input(INPUT_POST, 'type');
if(empty($type)){
    $type = 'json_encode';
}

$types = [
    'json_encode'=>'Json encode',
    'json_decode'=>'Json decode',
    'serialize'=>'Serialize',
    'unserialize'=>'Unserialize',
    'base64_encode'=>'Base64 encode',
    'base64_decode'=>'base64 decode',
    'rawurlencode'=>'Raw url encode',
    'rawurldecode'=>'Raw url decode',
    'urlencode'=>'Url encode',
    'urldecode'=>'Url decode',
    'utf8encode'=>'UTF8 encode',
    'utf8decode'=>'UTF8 decode',
    'utf8decode2'=>'UTF8 HEX to STR \xFF',
];

$form = [];
$form[] = html::form_item("Операция", html::radios('type', $types, $type), 1, '',[],true);
$form[] = html::form_item("Входящие данные", html::textarea("data", $data ,'Входящие данные',['style'=>'height: 30vh;']), 1);
$form[] =html::tag('p', html::input('submit', 'submit', 'Обработать'), ['class'=>'center']);
$submit = filter_input(INPUT_POST, 'submit');
$out = '';
function hexToStr($hex){
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2){
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
}
if(!empty($submit)){
    if(!empty($data)){
        switch($type){
            case 'json_encode':
                eval('$data='.$data.';');
                $out = json_encode($data);
                break;
            case 'json_decode': $out = json_decode($data);break;
            case 'serialize': $out = serialize($data); break;
            case 'unserialize': $out = unserialize($data); break;
            case 'base64_encode': $out = base64_encode($data); break;
            case 'base64_decode': $out = base64_decode($data); break;
            case 'rawurlencode': $out = rawurlencode($data); break;
            case 'rawurldecode': $out = rawurldecode($data); break;
            case 'urlencode': $out = urlencode($data); break;
            case 'urldecode': $out = urldecode($data); break;
            case 'utf8encode': $out = utf8_encode($data); break;
            case 'utf8decode': $out = utf8_decode($data); break;
            case 'utf8decode2':
                $out = $data;
                if(preg_match_all("/\\\x([0-9A-F]{2})/ui", $data,$ff)){
                    foreach($ff[1] as $k=>$f){
                        $out = str_replace($ff[0][$k], chr(hexdec($f)), $out);
                    }
                }
                break;
            default :
                $out = 'Неизвестная операция';
        }
        $out = print_r($out, TRUE);
    }else{
        $out = 'Входящие данные пусты';
    }
    
}
$form[] = html::form_item("Выходные данные", html::textarea("out", $out ,'Выходные данные',['style'=>'height: 30vh;','readonly'=>'readonly']), 1);

echo html::tag('form', implode("", $form), ['method'=>'POST']);

