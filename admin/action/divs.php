<?php
$theme->title(t('Организации'));
$sel = $DB -> prepare("SELECT * FROM `divs`");
$sel -> execute();
$items = array();
while($i = $sel -> fetch()){
    $items[] = array(
                $i['id'],
                $i['name'],
                $i['reg_mail'],
                $i['reg_fio'],
                $i['reg_date'],
                html::action('edit', 'edit_div', array('id'=>$i['id']), 'Редактировать')
            );
}
echo html::p(html::button('Добавить организацию', '', 'edit_div', array('id'=>0)));
echo html::table(array('#','Наименование','email','ФИО','Дата',''), $items, 1, array('class'=>'out_c'));