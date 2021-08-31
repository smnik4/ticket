/*local ticket script*/
var timer3 = false;
$(document).ready(function(){
    var save_timer = 0;
    var select_file = false;
    
    var saver = function(event){
        var mes = $('#edit_message_block').find("textarea").val();
        var save_timer = 0;
        if(mes.length > 0 && select_file == false){
            if(save_timer <= 0){
                save_timer = 31;
            }
            timer3 = window.setInterval(function(){
                    mes = $('#edit_message_block').find("textarea").val();
                    if(mes.length <= 0){
                        //clearInterval(timer3);
                        saver_stop();
                        return false;
                    }
                    save_timer = save_timer - 1;
                    $('#edit_message_block').find(".auto_save_time").html(save_timer);
                    if(save_timer <= 0){
                        save_form($('#edit_message_block').find("form").attr("id"));
                        clearInterval(timer3);
                        save_timer = 0;
                        select_file = false;
                    }
                }, 1000);
        }
    };
    
    var saver_stop = function(event){
        if($(this).is("input[type=file]")){
            select_file = true;
        }
        if(timer3){
            clearInterval(timer3);
            save_timer = 31;
            $('#edit_message_block').find(".auto_save_time").html("");
        }
    };
    
    $("form").on("change keyup",".complect_line .line_counter", function(event){
        //устанавливаем количество значений комплектов
        var block = $(this).parents(".complect_line");
        var def = $(this).parents(".complect_line").children(".line_default").html();
        var set = parseInt($(this).val());
        var val_c = $(this).parents(".complect_line").children(".line_values");
        var c = parseInt(val_c.children("div").length);
        if(set < 0){
            $(this).val(0);
            set = 0;
        }
        if(set > 20){
            $(this).val(20);
            set = 20;
        }
        if(set > c){
            var tl = '',tn = '',rn = '';
            while(set > c){
                c = c + 1;
                tl = '<div num="'+c+'">'+def+'</div>';
                val_c.append(tl)
                tn = $(tl).children("input").attr("name");
                rn = tn.replace("[]",'['+c+']');
                var aaa = block.find("div[num="+c+"]").children("input").attr("name",rn);
                var bbb = block.find("div[num="+c+"]").children("label");
                $.each(bbb,function(key,el){
                    rn = $(this).children("input").attr("name").replace("[]",'['+c+']');
                    $(this).children("input").attr("name",rn);
                });
            }
        }
        if(c > set){
            var last_div = null;
            while(c > set){
                last_div = val_c.children("div:last-child");
                if(last_div.is("div")){
                    last_div.remove();
                }
                c = c - 1;
            }
        }
    });
    
    //$("#edit_message_block").on("focusout",['#message_text','input[type=file]'], saver);
    
   // $("#edit_message_block").find('#message_text').on("focusin", saver_stop);
    $("#edit_message_block").on("focusin",'input[type=file]', function(e){
        select_file = false;
    });
   // $("#edit_message_block").on("click",'input[type=file]', saver_stop);
    $("#loginform").on('keypress','input',function(event){
        console.log(event);
        if(event.charCode == 13){
            save_form('loginform',false);
        }
    });
});