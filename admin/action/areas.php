<?php
$theme->title(t('Корпуса/Здания'));
$items = array();
$alldivs = get_sql_array('divs');
$divs = array();
if(in_array('root', $USER->groups)){
    $divs = $alldivs;
}elseif(isset($alldivs[$USER->div_id])){
    $divs[$USER->div_id] = $alldivs[$USER->div_id];
}
$colspan = 9;
foreach ($divs as $div_id=>$div_name){
    $sel = $DB -> prepare("SELECT * FROM `div_korpus` WHERE `div_id`=:div_id");
    $sel -> execute(array('div_id'=>$div_id));
    $items[] = array(html::td(sprintf('<b>%s</b>',$div_name), array('colspan'=>$colspan)));
    if($sel -> rowCount() > 0){
        while($i = $sel -> fetch()){
            $items[] = array(
                        $i['id'],
                        $i['name'],
                        $i['short_name'],
                        html::action('edit', 'edit_area', array('id'=>$i['id'],'div_id'=>$div_id), 'Редактировать')
                    );
        }
    }else{
        $items[] = array(html::td('Нет записей', array('colspan'=>$colspan)));
    }
    
}

echo html::p(html::button('Добавить', '', 'edit_area', array('id'=>0)));
echo html::table(array('#','Наименование','Кор.Наим.',''), $items, 1, array('class'=>'out_c'));