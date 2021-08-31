<h1 class="center"><?php echo t('Cистема заявок/задач'); ?></h1>
<?php
$theme->title(t("Cистема заявок/задач"));
$theme->set_css('/files/index.css');
$form = array();
$form[] = html::div(t('Авторизация'),array('class'=>'center'));
$form[] = html::hidden('action', 'login');
$form[] = html::form_item(t("Логин"), html::input('text', 'login', ''), 1);
$form[] = html::form_item(t("Пароль"), html::input('password', 'password', ''), 1);
$form[] = html::submit(t('Войти'), 'loginform', FALSE);
echo html::form(implode("",$form), 'loginform');
if($CONFIG['register'] > 0){
    echo html::tag('p', html::button_img(t('Регистрация'), 'ibutton', 'give_my_org'), array('class'=>'center'));
}
