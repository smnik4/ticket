<?php
$theme->title(t("IP утилиты"));
$preset_a = $address = filter_input(INPUT_GET, 'inpaddress');
$preset_o = $operation = filter_input(INPUT_GET, 'param');
$param = $USER->params('util');
if(empty($preset_a)){
    $preset_a = ifisset($param, 'inpaddress');
}
if(empty($preset_o)){
    $preset_o = ifisset($param, 'param');
}
$form = [];
$form[] = html::hidden('action', 'iputils');
$address;
$form[] = html::form_item(t('Узел'), html::input('text', 'inpaddress', $preset_a), 1);
$op = [
    'ping' => 'Ping',
    'traceroute' => 'TraceRoute',
    'nslookup' => 'NsLookUp',
];
if(empty($operation)){
    $opkeys = array_keys($op);
    $operation = array_shift($opkeys);
}
$form[] = html::form_item(t('Операция'), html::radios('param', $op, $preset_o), 1,'',[],1);
$form_id = 'iputils';
$form[] = html::submit(t('Вперед'), $form_id);
echo html::form($form, $form_id);
$res = '';
if(!empty($address) AND !empty($operation)){
    $res = iputil($operation, $address);
}
echo html::div($res,['id'=>'ipres']);