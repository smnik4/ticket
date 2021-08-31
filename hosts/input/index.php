<?php

include '../../assets/config.php';

$token = filter_input(INPUT_POST, 'token');
$file = (isset($_FILES['outfile']))?$_FILES['outfile']:FALSE;
$err = FALSE;
$div_id = 0;
if(empty($token)){
    $err = TRUE;
    echo "Token is empty\n";
}else{
    $sel = db("SELECT * FROM `divs` WHERE `token`=:token", ['token'=>$token]);
    if($sel ->rowCount() == 0){
        $err = TRUE;
        echo "Token not found\n";
    }else{
        $d = $sel -> fetch();
        $div_id = $d['id'];
    }
}
if($file == FALSE){
    $err = TRUE;
    echo "Input data not found\n";
}elseif($file['error'] > 0){
    $err = TRUE;
    $errcode = $file['error'];
    echo "Input file error. Code: $errcode\n";
}
if(!$err){
    host::parse_input_script($file['tmp_name'],$div_id);
    echo "Ok\n";
}

