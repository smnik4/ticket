<?php
$theme->title(t("Перевод метки времени UNIX в DATATIME"));
ini_set ('date.timezone', 'Asia/Omsk');
echo "Текущее время: ".time();
	$in_date = filter_input(INPUT_POST,"in_date");
	$in_date_d = filter_input(INPUT_POST,"in_date_d");
	$in_date_t = filter_input(INPUT_POST,"in_date_t");
	printf('<form method="POST">
			<input type="hidden" value="date" name="action">
			<input type="date" value="%s" name="in_date_d">
			<input type="time" value="%s" name="in_date_t"><br/>
			<textarea name="in_date" cols="30" rows="5">%s</textarea><br/>
			<input type="submit" value="Показать дату">
		</form>',
		$in_date_d,$in_date_t,
		$in_date);
	if(!empty($in_date)){
		echo "<ul>";
		$in_date = explode("\n",$in_date);
		foreach($in_date as $dd){
			$dd = (INT)$dd;
			if($dd > 0){
				echo "<li>" . date("Y-m-d H:i:s",$dd) . "</li>";
			}
		}
		echo "</ul>";
	}elseif(!empty($in_date_d) AND !empty($in_date_t)){
		echo "UNIX time: ";
		echo strtotime($in_date_d." ".$in_date_t.":00");
	}