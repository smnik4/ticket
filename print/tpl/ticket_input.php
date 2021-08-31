<?php
$id = filter_input(INPUT_GET,"ticket_id");
$data = get_ticket_data($id);
$html = sprintf('<table width="100%%" cellspacing="0" cellpadding="2" border="0">
		<tr>
			<td align="center"><h3>Заявка № %s</h3><h2>%s</h2></td>
			<td width="30%%" align="center">Отдел ПТОИ Центра информатизации ОмГПУ<hr>%s</td>
		</tr>
		</table>',
		$data['id'],
		$data['head'],
		date("Y.m.d H:i:s",$data['time'])
		);
$html .= '<br/>';
$host = '';
if($data['host_id'] > 0){
	$host = sprintf('<tr><th align="left">Имя хоста:</th><td>%s</td></tr>
		<tr><th align="left">MAC адрес:</th><td>%s</td></tr>',
		$data['host_name'],
		$data['host_mac']
		);
}
$html .= sprintf('<table width="100%%" cellspacing="0" cellpadding="2" border="1">
		<tr><th align="left">Ответственный:</th><td>%s</td></tr>
		<tr><th align="left">Инв. №:</th><td>%s</td></tr>
		<tr><th align="left">Наименование:</th><td>%s</td></tr>
		%s
		<tr><th align="left">МОЛ:</th><td>%s</td></tr>
		<tr><th align="left">Фирма:</th><td>%s</td></tr>
		<tr><th align="left">Поломка:</th><td>%s</td></tr>
		<tr><th align="left">Комплект:</th><td>%s</td></tr>
		<tr><th align="left">Ф.И.О.:</th><td>%s</td></tr>
		<tr><th align="left">Контакты:</th><td>%s</td></tr>
		<tr><th align="left">Примечание:</th><td>%s</td></tr>
		</table>',
		$data['user_fio']['FIO'],
		$data['inventory'],
		$data['inventory_name'],
		$host,
		$data['inventory_mol'],
		$data['priem_firm'],
		$data['priem_error'],
		$data['priem_komplekt'],
		$data['priem_fio'],
		$data['priem_phone'],
		$data['priem_comment']
		);
$html .= '<br/>';
$html .= sprintf('<table cellspacing="0" cellpadding="2" border="0">
		<tr><td>Принял</td><td style="width:150px;border-bottom:1px solid black;"> </td><td> / </td><td style="border-bottom:1px solid black;">%s</td></tr>
		<tr><td> </td><td align="center"><sub>Подпись</sub></td><td> </td><td align="center"><sub>ФИО</sub></td></tr>
		<tr><td>Выдал</td><td style="width:150px;border-bottom:1px solid black;"> </td><td> / </td><td style="width:150px;border-bottom:1px solid black;"> </td></tr>
		<tr><td> </td><td align="center"><sub>Подпись</sub></td><td> </td><td align="center"><sub>ФИО</sub></td></tr>
		</table>',
		$data['user_input']['FIO_1']
		);
//$html .= '<br/>';
$html = $html."<br/><hr/>".$html;
$html .= sprintf('<hr/><p><strong>Данные верны. "%s" %s %sг. _________________ / _________________</strong></p>',date("d"),$rusmonths_rod[(INT)date("m")],date("Y"));
$html .= '<hr/><p><strong>Оборудовние получил, претензий не имею. "___" ___________ 20 __ г. _________________ / _________________</strong></p>';