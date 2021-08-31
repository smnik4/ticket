<?php
	$theme->title("Статистика заявок по кабинетам");

	$start_date = filter_input(INPUT_GET,"stdt");
	if(!$start_date OR !preg_match("/^\d{4}-\d{2}-\d{2}$/",$start_date)){
		$start_date = strtotime(date("Y-m-01 00:00:00"));
	}else{
		$start_date = strtotime($start_date);
	}
	$end_date = filter_input(INPUT_GET,"endt");
	if(!$end_date OR !preg_match("/^\d{4}-\d{2}-\d{2}$/",$end_date)){
		$end_date = time();
	}else{
		$end_date = strtotime($end_date." 23:59:59");
	}
	if($start_date > $end_date){
		$start_date = strtotime(date("Y-m-01 00:00:00"));
	}
	printf('<form method="GET">
		<input type="hidden" name="action" value="stat_kab" />
		<input type="date" name="stdt" size="8" value="%s"> - 
		<input type="date" name="endt" size="8" value="%s"> 
		<input type="submit" value="&raquo;&raquo;&raquo;">
		</form>',
		date("Y-m-d",$start_date),
		date("Y-m-d",$end_date)
		);

	$kabinets = array();
	foreach($KORPUS as $key=>$val){
		$key = (INT)$key;
		//if($key < 7)
			$kabinets[$key] = array();
	}
	$new_kab = $kabinets;
	$sel = $DB -> prepare("SELECT T.id,T.korpus,T.kab FROM `tickets` T,`tickets_event` E WHERE T.id = E.ticket_id AND `korpus` > 0 AND `kab`!='' GROUP BY `korpus`,`kab` ORDER BY `kab`");
	$sel -> execute();
	while($row = $sel -> fetch()){
		$row['korpus'] = (INT)$row['korpus'];
		if(isset($kabinets[$row['korpus']])){
			$row['kab'] = explode(",",$row['kab']);
			foreach($row['kab'] as $kab){
				$kab = trim($kab);
				if(strlen($kab)>0){
					if(!in_array($kab,$kabinets[$row['korpus']])){
						$kabinets[$row['korpus']][] = $kab;
					}
				}
			}
		}
	}
	$all_summ = 0;
	foreach($kabinets as $korp => $val){
		$summ = 0;
		foreach($val as $kab){
			$sql = sprintf("SELECT *,MIN(E.dt) as mdt FROM `tickets` T,`tickets_event` E WHERE T.id = E.ticket_id AND `korpus` = %s AND `kab` LIKE '%%%s%%' GROUP BY T.id",$korp,$kab);
			$sel = $DB -> prepare($sql);
			$sel -> execute();
			$ts = 0;
			while($row = $sel -> fetch()){
				if($row['mdt'] > $start_date AND $row['mdt'] <= $end_date){
					$ts++;
				}
			}
			if($ts > 0){
				$new_kab[$korp][$kab] = $ts;
			}
			$summ += $ts;
		}
		$all_summ += $summ;
		$new_kab[$korp]['ALL_KAB'] = $summ;
	}
	echo '<table class="out_c" width="100%" border="0" cellspacing="0" cellpadding="2"><tr>
			<th>№</th>
			<th>'.t('Корпус').'</th>
			<th>Поступило заявок (%)</th>
		</tr>';
	$count = 0;
	foreach($new_kab as $korp => $val){
        $percent = 0;
        if($all_summ > 0){
            $percent = round(($val['ALL_KAB'] / $all_summ * 100),2);
        }
		$count += $val['ALL_KAB'];//<span class="stat_back" style="width:%s%%;"></span>
		printf('<tr>
			<td align="center">%s</td>
			<td><a href="?action=stat_kab&stdt=%s&endt=%s&korp=%s">%s</a></td>
			<td>
				<img src="/assets/img/stat_h.png" border="0" style="width: %spx;height:16px;float:left;"/>
				<span class="stat_text"><b>%s</b> (%s%%)</span>
			</tr>',
			$korp,
			date("Y-m-d",$start_date),
			date("Y-m-d",$end_date),
			$korp,
			$KORPUS[$korp],
			round($percent/1.2,2),
			$val['ALL_KAB'],
			$percent
			);
	}
	if($count > 0){
		printf('<tr class="stat_itog">
				<td colspan="2">ИТОГО</td>
				<td colspan="2">%s</td>
			  </tr>',$count);
	}
	echo '</table>';

$korp = filter_input(INPUT_GET,"korp");
if($korp){
	if(isset($new_kab[$korp])){
		if(count($new_kab[$korp]) > 1){
			printf('<h1>Статистика по: %s</h2>',$KORPUS[$korp]);
					echo '<table class="out_c" width="100%" border="0" cellspacing="0" cellpadding="2"><tr>
					<th>№</th>
					<th>Кабинет</th>
					<th>Поступило заявок (%)</th>
				</tr>';
			$nn = 0;
			$k_data = $new_kab[$korp];
			foreach($k_data as $key=>$val){
				if($key !== 'ALL_KAB'){
					$nn++;
					$percent = round(($val / $k_data['ALL_KAB'] *100),2);
					printf('<tr>
						<td align="center">%s</td>
						<td>%s</td>
						<td>
							<img src="/assets/img/stat_h.png" border="0" style="width: %spx;height:16px;float:left;"/>
							<span class="stat_text"><b>%s</b> (%s%%)</span>
						</tr>',
						$nn,
						$key,
						round($percent/1.2,2),
						$val,
						$percent
						);
				}
			}
			printf('<tr class="stat_itog">
				<td colspan="2">ИТОГО</td>
				<td colspan="2">%s</td>
			  </tr>',$k_data['ALL_KAB']);
			echo '</table>';
		}
	}
}