<?php
$theme->title(t('Группы заявок'));
$items = array();
$alldivs = get_sql_array('divs');
$divs = array();
if(in_array('root', $USER->groups)){
    $divs = $alldivs;
}elseif(isset($alldivs[$USER->div_id])){
    $divs[$USER->div_id] = $alldivs[$USER->div_id];
}
$colspan = 9;
$repair = group::repair_types();
foreach ($divs as $div_id=>$div_name){
    $sel = $DB -> prepare("SELECT * FROM `ticket_groups` WHERE `div_id`=:div_id");
    $sel -> execute(array('div_id'=>$div_id));
    $items[] = array(html::td(sprintf('<b>%s</b>',$div_name), array('colspan'=>$colspan)));
    if($sel -> rowCount() > 0){
        while($i = $sel -> fetch()){
            $items[] = array(
                        $i['id'],
                        $i['name_group'],
                        ifisset($repair, $i['repair_type'], ''),
                        html::action('edit', 'edit_group', array('id'=>$i['id'],'div_id'=>$div_id), 'Редактировать')
                    );
        }
    }else{
        $items[] = array(html::td('Нет записей', array('colspan'=>$colspan)));
    }
    
}

echo html::p(html::button('Добавить', '', 'edit_group', array('id'=>0)));
echo html::table(array('#','Наименование','Гр.ремонта',''), $items, 1, array('class'=>'out_c'));