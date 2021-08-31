<?php

//действия на странице
$action = Ticket::get_action();
$theme->action($action);

//Список заявок
print Ticket::load_ticket_list();

//показать передаваемые заявки
Ticket::get_give_to_my();

//обновления страницы
$theme->set_js(sprintf('var last_update = %s;',Ticket::last_users_action()));
$theme->set_js_timer('timer','update_tickets_list',10,true);
//$theme->set_js_timer('attentions_system','attentions_system',20);