<?php

$id = filter_input(INPUT_GET, "id");
$ticket = new Ticket($id);
//debug($ticket);
if(count($errors) > 0){
    $theme->title("Не найдена | Заявки");
}else{
    $theme->title(sprintf("%s | %s | Заявки",
        $ticket->head,
        $ticket->id));
    
    $theme->action(Ticket::get_action_ticket($ticket));
    
    printf('<div id="ticket_head">%s</div>',Ticket::view_ticket_head($ticket));
    printf('<div id="ticket_attachment">%s</div>',Ticket::view_attachment_ticket($ticket));
    printf('<div id="ticket_messages">%s</div>',Ticket::view_ticket_message($ticket));
    if(in_array($ticket->status, array(0,1))){
        printf('<div id="edit_message_block">%s</div>',Ticket::view_message_form($ticket->id));
    }
}

