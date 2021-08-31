<?php

class area{
    public $id = 0;
    public $div_id = NULL;
    public $name = NULL;
    public $short_name = NULL;
    
    public function __construct($id) {
        global $DB;
        if($id > 0){
            $this->id = $id;
            $sel = $DB->prepare("SELECT * FROM `div_korpus` WHERE `id`=:id");
            $sel->execute(array('id'=>$id));
            if($sel -> rowCount() > 0){
                $sel = $sel->fetch();
                $this->div_id = $sel['div_id'];
                $this->name = $sel['name'];
                $this->short_name = $sel['short_name'];
            }
        }
    }
    
    static public function edit_form($id,$div_id = 0) {
        global $USER;
        $data = new area($id);
        $form = array();
        if($USER->div_id > 0 AND $data->div_id == 0){
            $data->div_id = $USER->div_id;
        }
        $form[] = html::hidden('action', 'save_area');
        $form[] = html::hidden('area_id', $id);
        $divs = array();
        $alldivs = get_sql_array('divs');
        if(in_array('root', $USER->groups)){
            $divs = $alldivs;
        }elseif(isset($alldivs[$USER->div_id])){
            $divs[$USER->div_id] = $alldivs[$USER->div_id];
        }
        $form[] = html::form_item('Организация',html::select('div_id', $divs, $data->div_id),1);
        $form[] = html::form_item('Наименование', html::input('text', 'name', ifisset($data, 'name')),1);
        $form[] = html::form_item('Кор.Наим.', html::input('text', 'short_name', ifisset($data, 'short_name')),1);
        $form_id = 'edit_area';
        $form[] = html::submit('Сохранить', $form_id);
        return html::form(implode("",$form), $form_id);
    }
    
    static public function save() {
        global $DB;
        $valid = self::valid_form();
        if($valid !== FALSE){
            $sq = $DB -> prepare($valid['sql']);
            $sq -> execute($valid['data']);
            if($sq -> rowCount() > 0){
                return TRUE;
            }else{
                set_error('Ошибка запроса. Возможно Вы ничего не поменяли.');
            }
        }
        return FALSE;
    }
    
    static public function valid_form() {
        global $USER;
        $fields = array(
            'div_id' => array('value'=>'div_id','name'=>t('Организация')),
            'name' => array('value'=>'name','name'=>'Наименование'),
            'short_name' => array('value'=>'short_name','name'=>'Кор.Наим.'),
        );
        $f = $v = $data = array();
        $pass = TRUE;
        $id = filter_input(INPUT_POST, 'area_id',FILTER_VALIDATE_INT);
        $data_t = array();
        if($id > 0){
            $data_t = new div($id);
        }
        foreach($fields as $key=>$field){
            $value = filter_input(INPUT_POST, $field['value']);
            $value = trim($value);
            $value = htmlspecialchars($value);
            if(mb_strlen($value) == 0 OR is_null($value)){
                set_error_field($field['value']);
                set_error(sprintf('Ошибка в поле "%s"',$field['name']));
                $pass = FALSE;
            }
            if($pass){
                $data[$key] = $value;
                if($id > 0){
                    $f[] = sprintf('`%s`=:%s',$key,$key);
                }else{
                    $f[] = sprintf('`%s`',$key);
                    $v[] = sprintf(':%s',$key);
                }
            }
        }
        if($pass){
            if($id > 0){
                $data['id'] = $id;
                $sql = sprintf("UPDATE `div_korpus` SET %s WHERE `id`=:id",implode(", ",$f));
            }else{
                $sql = sprintf("INSERT INTO `div_korpus`(%s) VALUES (%s)",implode(", ",$f),implode(", ",$v));
            }
            return array('data'=>$data,'sql'=>$sql);
        }
        return FALSE;
    }
    
    static public function check_field($field,$value) {
        global $DB;
        $sel = $DB -> prepare("SELECT * FROM `divs` WHERE `".$field."`=:field");
        $sel -> execute(array('field'=>$value));
        if($sel -> rowCount() > 0){
            return FALSE;
        }
        return TRUE;
    }
}