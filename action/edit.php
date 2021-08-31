<form method="POST" id="edit_form" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save_ticket" />
<?php

/*
 * PRESET GET
 * head
 * area
 * host_id -> inventory, korpus, kab
 * info
 *  */

$id = (INT)filter_input(INPUT_GET, "id");
printf('<input type="hidden" name="id" value="%s" />',$id);
printf('<input type="hidden" name="form_id" value="%s" />', md5(time()));
$ticket = new Ticket($id);
$theme->title($ticket->window_head,TRUE);
if(!$ticket->is_editor){
    $theme->access(FALSE);
    $theme->create();
    exit();
}
$form = array();
if($id){
    printf('<input type="hidden" name="user_id" value="%s" />', $ticket->user_id);
}
$form['title'] = array(
    'name'=>array(
        'value'=>'Заголовок<span class="red bold">*</span>',
        'attr'=>array('width'=>'150px'),
        ),
    'field'=>sprintf('<input type="text" name="head" value="%s"/>',$ticket->head)
    );
$areas = Ticket::get_area();
$form['area'] = array(
    'name'=>'Направление<span class="red bold">*</span>',
    'field'=>get_select($areas,"area",$ticket->area,' onchange="set_var(this,\'ticket_edit_var\',\'area\',\'update_edit_form\','.$id.');"')
    );
if(!$id){
    $users = [0=> 'Нет ответственного'] + Ticket::get_users();
    $form['user_id'] = array(
        'name'=>'Ответственный',
        'field'=>get_select($users,"user_id",$ticket->user_id)
        );
}

if($ticket->status == 3){
    $form['status'] = array(
    'name'=>'Возобновить заявку <span class="red bold">*</span>',
    'field'=>'<label><input type="radio" name="status" value="1" /> Да</label> <label><input type="radio" name="status" value="3" /> Нет</label>'
    );
}
$form['state'] = array(
    'name'=>'Состояние',
    'field'=>get_select($STATE,"state",$ticket->state)
    );
$form['korpus'] = array(
    'name'=>t('Корпус'),
    'field'=>get_select($KORPUS,"korpus",$ticket->korpus,'','','Не указан')
    );
//' onchange="set_var(this,\'ticket_edit_var\',\'korpus\',\'update_edit_form_base\');"'
$form['kab'] = array(
    'name'=>'Кабинет',
    'field'=>sprintf('<input type="text" name="kab" value="%s" />',$ticket->kab)
    //onkeyup="set_var(this,\'ticket_edit_var\',\'kab\',false);"
    );
$form['inventory'] = array(
    'name'=>'Инвентарный номер<span class="red bold" id="inventory_field_label">*</span>',
    'field'=>array(
        'attr'=>array('class'=>"ticket_edit_inventory"),
        'value'=>sprintf('<input type="text" name="inventory" value="%s" autocomplete="off" />',$ticket->inventory),
        )
    );
$form['priem_head'] = array(
    'attr'=>array('class'=>'ticket_priem_line'),
    'name'=>array(
        'attr'=>array('colspan'=>"2"),
        'value'=>'',
        ),
    );
$form['event_dt'] = array(
    'name'=>'Дата начала',
    'field'=>sprintf('<input type="date" name="event_dt" value="%s" />',$ticket->event_dt)
    );
$form['event_time'] = array(
    'name'=>'Время начала',
    'field'=>sprintf('<input type="time" name="event_time" value="%s" />',$ticket->event_time)
    );
if(!$id){
    $form['message'] = array(
        'name'=>'Доп. информация',
        'field'=>sprintf('<textarea name="message" rows="4" style="width:100%%">%s</textarea>',$ticket->info)
        );
}else{
    $form['attach'] = array(
        'name'=>'Прикрепления',
        'field'=>array(
            'attr'=>array(),
            'value'=>''. File::form_max_size('<input type="file" name="attach[]" multiple/>')
            )
        );
}

echo table($form,array('width'=>'100%'));
?>
    <div class="field full center"><input type="button" value="Сохранить" onclick="save_form();" /></div>
</form>
<?php
print '<script type="text/javascript" charset="UTF-8">'
. '$(document).ready(function(){
        exec_remote("update_edit_form",false,{"id":'.$id.'});
    });'
. '</script>';