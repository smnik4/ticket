window.onscroll = function() {
	var top = document.body.scrollTop;
	if(top > 200){
		$(".up").fadeIn();
	}else{
		$(".up").fadeOut();
	}
}

$(document).ready(function(){
    $('.up').click(function() {
        if($('.up').attr("no_top") != "true"){
            $('html, body').animate({scrollTop: 0},500);
               return false;
        }
    });
    if($('.up').is("a")){
        $('.up').draggable({
            stop:function(){
                $(this).attr("no_top","true");
                setTimeout(function(){
                    $('.up').removeAttr("no_top");
                },2000);
            }
	});
    }
	
    $(".select_table_line").click(function(){
        //выделение строки в таблице по клику
        $(".select_table_line.select").attr("class","select_table_line");
        $(this).attr("class","select_table_line select");
    });
	
    $(".close_window").click(function(){
        //закрытие центрального окна
        $(".parent_window").attr("hidden","hidden");
    });
    
    $(".autocomplete").on("click","ul", function(event){
        console.log($(this).html());
    });
    
    
    $("#lic_product").on("change", function(event){
		get_lic_version();
	});
	$(".cartr_image").on("mouseenter", function(event){
		var vid = $(this).attr("vid");
		var leftt = $("body").width() - event.pageX + 20;
		var topss = event.pageY - $(window).scrollTop()-100;
		$("#img_show").css({'top':topss,'right':leftt});
		$("#img_show").html('<img border="0" src="images.php?img_id='+vid+'">');
		$("#img_show").show();
	});
	$(".cartr_image").on("mouseout", function(event){
		$("#img_show").hide();
	});
	$(".slides").on("click", function(event){
		var cc = $(this).parent(".info_block").children("content");
		if(cc.css("display") == "none"){
			cc.show();
		}else{
			cc.hide();
		}
		return false;
	});
});

function open_win(script,id,width,height,query) {  
    var top = screen.height/2-height/2;
    var left = screen.width/2-width/2;
    popupWin = window.open(script+"?"+query,'Tickets',"top="+top+",left="+left+",width="+width+",height="+height+",scrollbars=yes"); 
    popupWin.focus();
}
function close_win(id){
    var win=document.getElementById(id);
    var mask=document.getElementById("mask");
    mask.style.display='none';
    win.style.display='none';
}

function wirdow_res(width){
	var marg = 0,
		height = parseInt($(".window").css("height"))/2;
		tops = parseInt($(".window").css("top"));
		width_w = parseInt($("body").css("width"))-30;
	tops = tops-height;
	if(tops < 0){
		height = height + tops;
	}
	if(width > width_w){
		width = width_w;
	}
	marg = width/2;
	$(".window").attr("style","width:"+width+"px;margin: -"+height+"px 0 0 -"+marg+"px;");
}
function show_in_number(id){
	$(".parent_window").removeAttr("hidden");
	$(".window").attr("hidden","hidden");
	$(".rotater").removeAttr("hidden");
    $.post("/hosts/request.php",{action:"show_in_number",id:id,'full':true},function(data){
		if(parseInt(data.status)===1){
			$(".window").removeAttr("hidden");
			$(".window_title").html(data.title);
			$(".window_content").html(data.content);
			$(".window_error").html(data.message);
			$(".window").removeAttr("hidden");
			wirdow_res(700);
		}else{
			alert(data.message);
			$(".parent_window").attr("hidden","hidden");
		}
		//console.log($data);
		$(".rotater").attr("hidden","hidden");
    },'json');
}

function generate_form(){
	var area = $("select[name='area']").val();
	if(area == 3 || area == 112){
		$(".remont_block").show();
	}else{
		if(area == 1){
			$(".remont_block").hide();
			$(".input_tick_block").show();
		}else{
			$(".remont_block").hide();
		}
		
	}
}

function get_inventory_id(){
	var inv = $("input[name='inventory']").val();
	if(inv.length>4){
		$.post("/hosts/request.php",{action:"get_inventory_id",'inv':inv},function(data){
			if(parseInt(data.status)===1){
				$("select[name='inventory_id']").html(data.content);
				get_host_id();
			}else{
				alert(data.message);
			}
		},'json');
	}
}

function get_host_id(){
	var inv = $("select[name='inventory_id']").children("option:selected").attr("num");
	var korpus = $("select[name='korpus']").val();
	var kab = $("input[name='kab']").val();
	$.post("/hosts/request.php",{action:"get_host_id",'inv':inv,'korpus':korpus,'kab':kab},function(data){
		if(parseInt(data.status)===1){
			$("td.host_id").html(data.content);
		}else{
			alert(data.message);
		}
	},'json');
}

function set_error_mac_ignored(mac){
	if(confirm("Игнорировать MAC адрес при следующих обновлениях.")){
		$.post("/diag/action_json.php",{action:"set_mac_ignore",'mac':mac},function(data){
			if(parseInt(data.status)===1){
				$("#"+mac).css("background-color","red");
			}else{
				alert(data.message);
			}
			$(".rotater").attr("hidden","hidden");
		},'json');
	}
}

function not_show_in_number(id){
	if(confirm("Удалить все записи с таким наименованием.")){
		$.post("/hosts/request.php",{action:"not_show_in_number",id:id},function(data){
			if(parseInt(data.status)===1){
				$(".parent_window").attr("hidden","hidden");
			}else{
				$(".window_error").html(data.message);
			}
			$(".rotater").attr("hidden","hidden");
		},'json');
	}
}

function set_client_info(){
	var fio = $("#fio").val();
		house = $("#house").val(),
		room  = $("#room").val(),
		phone = $("#phone").val(),
		dop_i = $("#dops_i").val(),
		pppoe_login = $("#pppoe_login").val(),
		pppoe_pass = $("#pppoe_pass").val(),
		new_password = $("#new_password").val(),
		err = false,
		err_text = '<ul>',
		color = "#F4BEBE";
	if(fio == ''){
		err = true;
		err_text = err_text +"<li>Введите ФИО!</li>";
		$("#fio").css("background-color",color);
	}else{
		$("#fio").css("background-color","white");
	}
	if(pppoe_login == ''){
		err = true;
		err_text = err_text +"<li>Логин доступа PPPOE!</li>";
		$("#pppoe_login").css("background-color",color);
	}else{
		$("#pppoe_login").css("background-color","white");
	}
	if(pppoe_pass == ''){
		err = true;
		err_text = err_text +"<li>Пароль доступа PPPOE!</li>";
		$("#pppoe_pass").css("background-color",color);
	}else{
		$("#pppoe_pass").css("background-color","white");
	}
	if(new_password == ''){
		err = true;
		err_text = err_text +"<li>Пароль в личный кабинет!</li>";
		$("#new_password").css("background-color",color);
	}else{
		$("#new_password").css("background-color","white");
	}
	if(house == 0){
		err = true;
		err_text = err_text +"<li>Выберите общежитие!</li>";
		$("#house").css("background-color",color);
	}else{
		$("#house").css("background-color","white");
	}
	if(room == ''){
		err = true;
		err_text = err_text +"<li>Введите номер комнаты!</li>";
		$("#room").css("background-color",color);
	}else{
		$("#room").css("background-color","white");
	}
	if(phone == ''){
		err = true;
		err_text = err_text +"<li>Введите номер телефона!</li>";
		$("#phone").css("background-color",color);
	}else{
		if(!/^\d{10}$/.test(phone) && !/^\d{6}$/.test(phone)){
			err = true;
			err_text = err_text +"<li>Не верный формат номера телефона!</li>";
			$("#phone").css("background-color",color);
		}else{
			$("#phone").css("background-color","white");
		}
	}
	err_text = err_text +'</ul>';
	if(err){
		$("#form_err").html(err_text);
		$("textarea[name='info']").val("");
	}else{
		$("#form_err").html("");
		$("textarea[name='info']").val(house+":"+room+":"+phone+":"+dop_i);
	}
}
function close_window(){
	$(".parent_window").attr("hidden","hidden");
}
function form_window(id){
	$(".window_title").html("Данные для договора и отправки в смартком");
	$(".window_content").html('<p><label class="form_label">Удостоверяющий документ:</label>'
		+' <label><input type="radio" name="Udoc" value="pasp" onClick="get_Udoc_field(\'Udoc\');"> Паспорт</label>'
		+' <label><input type="radio" name="Udoc" value="UDL" onClick="get_Udoc_field(\'Udoc\');"> Удостоверение личности</label></p>'
		+'<p id="UdocField"></p>'
		+'<p><label class="form_label">Адрес регистрации (проживания для иностранных):</label>'
		+' <input type="text" style="width:100%;" id="addres"></p>'
		+'<p><label><input type="checkbox" id="adres_podkl" value="1" onClick="disable_el(\'addres\')"> Совпадает с адресом подключения</label></p>'
		+'<p><label class="form_label">E-mail отправки:</label>'
		+' <input type="text" style="width:100%;" id="sent_email" value="abon@smartkom.ru"></p>'
		//+' <input type="text" style="width:100%;" id="sent_email" value="smnik@omgpu.ru"></p>'
		//+' <input type="text" style="width:100%;" id="sent_email" value="251519@omgpu.ru"></p>' 
		+'<p style="display: none;"><label><input type="checkbox" id="not_send_mail" value="1" onClick="disable_el(\'sent_email\')"> Не отправлять данные на почту</label></p>'
		+'<p align="center"><input type="hidden" id="vid" value="'+id+'">'
		+'<input type="button" value="Выполнить" onClick="send_for_dogovor();"></p>');
	$(".window_error").html("");
	$(".parent_window").removeAttr("hidden");
	$(".rotater").removeAttr("hidden");
	$(".window").removeAttr("hidden");
	wirdow_res(500);
}
function disable_el(elid){
	if($("#"+elid).attr("readonly")){
		$("#"+elid).removeAttr("readonly");
		$("#"+elid).css("background-color","white");
	}else{
		$("#"+elid).attr("readonly","readonly");
		$("#"+elid).css("background-color","gray");
	}
}
function get_Udoc_field(name){
	var docs = $("input[name='"+name+"']:checked").val();
	if(docs == "pasp"){
		$("#UdocField").html('Серия <input type="text" size="10" id="serial" placeholder="xxxx"> Номер <input type="text" size="10" id="nomber" placeholder="xxxxxx">');
	}else{
		$("#UdocField").html('<input type="hidden" id="serial" value="0000"> Номер <input type="text" size="10" id="nomber" placeholder="xxxxxx">');
	}
}

function send_for_dogovor(){
	var id = '',
		docs = '',
		serial = '',
		nomber = '',
		addres = '',
		adres_podkl = 0,
		email = '',
		email_not = 0,
		err = true;
	
	docs = $("input[name='Udoc']:checked").val();
	id = $("#vid").val();
	if(docs == "pasp" || docs == "UDL"){
		err = false;
		$(".window_error").html("");
		serial = $("#serial").val();
		nomber = $("#nomber").val();
		if(docs == "pasp"){
			if(serial == '' || nomber == ''){
				err = true;
				$(".window_error").html("Не указаны серия/номер паспорта!");
			}else{
				err = false;
			}
		}else{
			if(nomber == ''){
				err = true;
				$(".window_error").html("Не указан номер удостоверения личности!");
			}else{
				err = false;
			}
		}
		email = $("#sent_email").val();
		email_not = document.getElementById("not_send_mail").checked;
		if(email_not){
			email_not = 1;
		}
		if(email == '' && email_not == 0){
			err = true;
			$(".window_error").html("Не указан email!");
		}else{
			err = false;
		}
		if(!err){
			addres = $("#addres").val();
			adres_podkl = document.getElementById("adres_podkl").checked;
			if(adres_podkl){
				adres_podkl = 1;
			}
			if(addres == '' && adres_podkl == 0){
				err = true;
				$(".window_error").html("Не указан адрес регистрации!");
			}else{
				err = false;
			}
		}
	}else{
		$(".window_error").html("Не указан удостоверяющий документ!");
		err = true;
	}
	if(!err){
		$(".window_error").html('');
		$.post('print_full_dog.php', {action:'get_dogovor',id:id,docs:docs,serial:serial,nomber:nomber,addres:addres,adres_podkl:adres_podkl,email:email,email_not:email_not}, function(data){
			//console.log(data);
			if(data.status == 1){
				window.location = data.link;
			}
			//$(".window_error").html(data);
			$(".window_error").html(data.message);
		},'json');
	}
}

function delete_cart_out(id){
	if(confirm("Отмена не возможна!\nУдалить запись?")){
		$.post('action_json.php', {action:'delete_out',id:id}, function(data){
			if(data.status == 1){
				$("#cartr_out_"+id).remove();
			}else{
				alert(data.message);
			}
		},'json');
	}
}

function delete_div(id){
	if(confirm("Отмена не возможна!\nУдалить запись?")){
		$.post('action_json.php', {action:'delete_div',id:id}, function(data){
			if(data.status == 1){
				document.location.reload(true);
			}else{
				alert(data.message);
			}
		},'json');
	}
}

function edit_div(id){
	$(".parent_window").removeAttr("hidden");
	$(".window").attr("hidden","hidden");
	$(".rotater").attr("hidden","hidden");
	$(".window").removeAttr("hidden");
	$(".window_title").html("Редактирвоание подразделения");
	$(".window_content").html('<p class="center"><img src="/img/load.gif" ></p>');
	$(".window_error").html("");
	wirdow_res(500);
	$(".window").removeAttr("hidden");
	$.post('action_json.php', {action:'get_edit_div',id:id}, function(data){
			if(data.status == 1){
				$(".window_content").html(data.html);
			}else{
				$(".window_error").html(data.message);
				$(".window_content").html("");
			}
			if(data.debug){
				console.log(data.debug);
			}
	},'json');
}

function delete_cart_input(id){
	if(confirm("Отмена не возможна!\nУдалить запись?")){
		$.post('action_json.php', {action:'delete_input',id:id}, function(data){
			if(data.status == 1){
				$("#cart_input_"+id).remove();
			}else{
				alert(data.message);
			}
		},'json');
	}
}
function on_checked_element(el,dis){
	if($(el+":checked").is("input")){
		$(dis).show();
	}else{
		$(dis).css("display","none");
	}
}

function on_checked_element_dis(el,dis,inactive){
	if($(el+":checked").is("input")){
		//$(dis).show();
		$(dis).css("display","none");
		$(inactive).removeAttr("checked");
		$(inactive).attr("disabled","disabled");
	}else{
		$(inactive).removeAttr("disabled");
	}
}

function edit_cart_input(id){
	$(".parent_window").removeAttr("hidden");
	$(".window").attr("hidden","hidden");
	$(".rotater").attr("hidden","hidden");
	$(".window").removeAttr("hidden");
	$(".window_title").html("Редактирвоание информации о приемке");
	$(".window_content").html('<p class="center"><img src="/img/load.gif" ></p>');
	$(".window_error").html("");
	wirdow_res(500);
	$(".window").removeAttr("hidden");
	$.post('action_json.php', {action:'get_input_edit',id:id}, function(data){
			if(data.status == 1){
				$(".window_content").html(data.html);
			}else{
				$(".window_error").html(data.message);
				$(".window_content").html("");
			}
			if(data.debug){
				console.log(data.debug);
			}
	},'json');
}

function delete_my_spare(id){
	if(confirm("Отмена не возможна!\nУдалить запись?")){
		$.post('action_json.php', {action:'delete_my_spare',id:id}, function(data){
			if(data.status == 1){
				$("#my_spare_"+id).remove();
			}else{
				alert(data.message);
			}
		},'json');
	}
}

function delete_spare_out(id){
	if(confirm("Отмена не возможна!\nУдалить запись?")){
		$.post('action_json.php', {action:'delete_spare_out',id:id}, function(data){
			if(data.status == 1){
				$("#spare_out_list_"+id).remove();
			}else{
				alert(data.message);
			}
		},'json');
	}
}

function set_print(){
	var e = $("#print_stat");
	if(e.attr("action")){
		e.removeAttr("action");
	}else{
		e.attr("action","/cartridges/print.php");
	}
}

function get_lic_version(){
	var prod_arr = $("#lic_product").val();
	$.post('action_json.php', {action:'get_lic_version','prod_arr':prod_arr}, function(data){
			if(data.status == 1){
				$("#lic_vers_block").html(data.html);
			}else{
				alert(data.message);
			}
		},'json');
}

function get_all_lic(){
	var prod_arr = $("#lic_product").val();
	var vers_arr = $("#lic_version").val();
	$.post('action_json.php', {action:'get_all_lic','prod_arr':prod_arr,'vers_arr':vers_arr}, function(data){
			if(data.status == 1){
				$("#lic_content").html(data.html);
			}else{
				alert(data.message);
			}
		},'json');
}

function delete_inst_key(id){
	if(confirm("Удалить ключ с хоста?")){
		$.post('/license/action_json.php', {action:'delete_inst_key','inst_key_id':id}, function(data){
			if(data.status == 1){
				document.location.reload(true);
			}else{
				alert(data.message);
			}
		},'json');
	}
}

function search_license(){
	var lic_number = $("input[name=lic_number]"),
		lic_program = $("input[name=lic_program]"),
		lic_key = $("input[name=lic_key]");
	if(lic_number.val() == "" && lic_program.val() == "" && lic_key.val() == ""){
		lic_number.css({"background-color":"#FFC7AD","color":"#8c2e0b"});
		lic_program.css({"background-color":"#FFC7AD","color":"#8c2e0b"});
		lic_key.css({"background-color":"#FFC7AD","color":"#8c2e0b"});
	}else{
		lic_number.css({"background-color":"white","color":"black"});
		lic_program.css({"background-color":"white","color":"black"});
		lic_key.css({"background-color":"white","color":"black"});
		$.post('action_json.php', {action:'search_license','lic_number':lic_number.val(),'lic_program':lic_program.val(),'lic_key':lic_key.val()}, function(data){
			if(data.status == 1){
				$("#lic_content").html(data.html);
			}else{
				alert(data.message);
			}
		},'json');
	}
}

function set_new_joinport(host){
	if(confirm("Подтвердите назначение новых портов сопряжения!")){
		$.post('/hosts/request.php', {action:'set_new_joinport','host':host}, function(data){
			if(data.status == 1){
				document.location.reload(true);
			}else{
				alert(data.message);
			}
		},'json');
	}
}

function save_join_pre(){
	var ports = $("#new_join_ports").val(),
		host = $("#new_join_host").val();
	if(ports == "" || host == ""){
		$(".window_error").html("Не корректные значения портов или имени хоста!");
	}else{
		$(".window_error").html("");
		$.post('/hosts/request.php', {action:'set_new_joinport','host':host,'ports':ports}, function(data){
			if(data.status == 1){
				document.location.reload(true);
			}else{
				$(".window_error").html(data.message);
			}
		},'json');
	}
}

function set_new_join_pre(host,ports){
	$(".parent_window").removeAttr("hidden");
	$(".window").attr("hidden","hidden");
	$(".rotater").attr("hidden","hidden");
	$(".window").removeAttr("hidden");
	$(".window_title").html("Корректировка портов сопряжения");
	$(".window_content").html('<table width="100%"><tr><td><b>Хост:</b></td><td>'+host+'</td></tr>'+
		'<tr><td><b>Порты:</b></td><td><input type="text" value="'+ports+'" id="new_join_ports">'+
		'<input type="hidden" id="new_join_host" value="'+host+'"></td></tr>'+
		'<tr><td colspan="2" align="center"><input type="button" onclick="save_join_pre();" value="Сохранить"></td></tr><table>');
	$(".window_error").html("");
	$(".window").removeAttr("hidden");
	wirdow_res(300);
}
function install_key_to(key_id,prod_name,key_val){
	$(".parent_window").removeAttr("hidden");
	$(".window").attr("hidden","hidden");
	$(".rotater").removeAttr("hidden");
    $.post("action_json.php",{action:"get_host_select"},function(data){
		if(parseInt(data.status)===1){
			$(".window").removeAttr("hidden");
			$(".window_title").html(data.title);
			$(".window_content").html(
				'<input type="hidden" name="key_id" value="'+key_id+'">'+
				'<input type="hidden" name="prod_name" value="'+prod_name+'">'+
				'<input type="hidden" name="key_val" value="'+key_val+'">'+
				'<table width="100%">'+
				'<tr><td width="120">Продукт</td><td>'+prod_name+'</td></tr>'+
				'<tr><td>Ключ</td><td>'+key_val+'</td></tr>'+
				'<tr><td>Хост</td><td>'+data.html+'</td></tr>'+
				'<tr><td colspan="2" id="host_info"></td></tr>'+
				'<tr><td colspan="2" align="center"><input type="button" value="Сохранить" onclick="save_key_to();"></td></tr>'+
				'</table>');
			$(".window_error").html(data.message);
			$(".window").removeAttr("hidden");
			wirdow_res(600);
		}else{
			alert(data.message);
			$(".parent_window").attr("hidden","hidden");
		}
		$(".rotater").attr("hidden","hidden");
    },'json');
}

function get_host_data(content){
	var host_id = $("select[name=host_id]").val();
	if(host_id > 0){
		$.post("action_json.php",{action:"get_host_data",'host_id':host_id},function(data){
			if(parseInt(data.status)===1){
				$(content).html(data.html);
			}else{
				$(".window_error").html(data.message);
			}
		},'json');
	}else{
		$(content).html("");
	}
    
}
function save_key_to(){
	var key_id = $("input[name=key_id]").val(),
		prod_name = $("input[name=prod_name]").val(),
		key_val = $("input[name=key_val]").val(),
		host_id = $("select[name=host_id]").val();
	//alert(key_id+"**"+prod_name+"**"+key_val+"**"+host_id)
	if(prod_name !== "" && key_val !== "" && key_id > 0 && host_id > 0){
		$(".window_error").html("");
		$.post('action_json.php', {action:'save_key_to_host','key_id':key_id,'prod_name':prod_name,'key_val':key_val,'host_id':host_id}, function(data){
			if(data.status == 1){
				document.location.reload(true);
			}else{
				$(".window_error").html(data.message);
				if(data.log){
					console.log(data.log);
				}
			}
		},'json');
	}else{
		$(".window_error").html("Не корректные значения параметров!");
		
	}
}

function get_pre_fio(){
	var fio = $("input[name=out_fio]").val();
	if(fio.length > 2){
		$.post('action_json.php', {action:'get_pre_fio','fio':fio}, function(data){
			if(data.status == 1){
				$("#pre_fio").html(data.html);
			}else{
				alert(data.message);
			}
		},'json');
	}
}

function set_pre_fio(fio){
	$("input[name=out_fio]").val(fio);
	$("#pre_fio").html("");
}

/* 
	Скрипт показа сообщений
 */

function close_message(id){
	var block = $("#block_"+id);
	if(block.css("display") == "block"){
		block.remove();
	}
}

function show_message(text,type,ad_class) {
	if(!type){
		type = "error";
	}
	if($("."+ad_class).is("div")){
		return false;
	}
	if(!ad_class){
		ad_class = "";
	}else{
		ad_class = ' '+ad_class;
	}
	var ew = $("#block_info");
	var blockid = ew.children("div").length + Date.now ();
	ew.append('<div title="Нажмите для скрытия сообщения" class="'+type+ad_class+'" id="block_'+blockid+
				'" onclick="close_message('+blockid+');"><p>'+text+'</p></div>');
	var block = $("#block_"+blockid);
	block.slideDown(400,function(){
		if(block.css("display") == "block"){
			//var time = Math.round(text.length / 15 * 1000);
			var time = Math.round(text.length * 1000);
			block.delay(time).slideUp(400,function(){
				block.remove();
			});
		}
		
	});
}