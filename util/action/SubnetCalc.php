<?php
$theme->title(t('IP калькулятор'));

echo ipcalc::form();
echo html::div('', ['id'=>'calcres']);

$my_net_info =  trim(filter_input(INPUT_POST, 'my_net_info'));
if(!empty($my_net_info)){
    set_param('util', 'net_info', $my_net_info);
}else{
    $param = $USER->params('util');
    $my_net_info = ifisset($param, 'net_info');
}
