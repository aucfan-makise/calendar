<?php 
	require "CalendarData.php";
	require "CurrentCalendarData.php";
	require "CalendarPrinter.php";
?>
<html>
	<head>
		<title>Calendar</title>
		<link rel="stylesheet" type="text/css" href="./calendar.css">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	</head>
	<body>
<?php 
	$calendarNum = 3;
	$currentCalendarData = new CurrentCalendarData();
	$centerCalendarData = new CurrentCalendarData();
	
// 	次・前のカレンダーの遷移操作
	if($_GET["pre"] != null)
		$centerCalendarData = new CalendarData($_GET["pre"]);
	elseif($_GET["next"] != null)
		$centerCalendarData = new CalendarData($_GET["next"]);
	elseif($_GET["date_select"] != null)
		$centerCalendarData = new CalendarData($_GET["date_select"]);
	else
		$centerCalendarData = new CalendarData($currentCalendarData->getYear()."-".$currentCalendarData->getMonth());
	
// 	カレンダーの数
	if($_GET["calendarNum"] != null)
		$calendarNum = $_GET["calendarNum"];
?>
		<form method="get" action="calendar.php">
			<button name="pre" value="
<?php echo date('Y-m', strtotime($centerCalendarData->getYear()."-".$centerCalendarData->getMonth()." -1 month")) ?>
			">前</button>
			<button name="next" value="
<?php echo date('Y-m', strtotime($centerCalendarData->getYear()."-".$centerCalendarData->getMonth()." +1 month")) ?>
			">次</button>

<!-- コンボボックス -->
			<p>
				<select name="date_select">
<?php 
		for($i = -10; $i <= 10; ++$i){
			list($year, $month) = $currentCalendarData->getCalcMonth($i);
			$value = $year."-".$month;
			$output = $year."年".$month."月";
			if ($centerCalendarData->getYear() == $year && $centerCalendarData->getMonth() == $month){
?>
				<option value="<?php echo $value ?>" selected><?php echo $output ?></option>
<?php 		} else ?>
				<option value="<?php echo $value ?>"><?php echo $output ?></option>
<?php	} ?>
				</select>
				<input type="submit" value="select">
			</p>
			<p>
			表示するカレンダーの数
				<input type="text" size=2 maxlength="2" name="calendarNum" value="<?php echo $calendarNum ?>">
				<input type= "submit" value="change">
			</p>
		</form>
		<table>
			<tr>
<?php		
// カレンダーの表示
		$calendarLoopStart = "-".floor($calendarNum / 2);
		$calendarLoopEnd = ceil($calendarNum / 2);
		for ($i = $calendarLoopStart; $i < $calendarLoopEnd; ++$i){
			$calc = $centerCalendarData->getCalcMonth($i);
			if ($calc[0] == $currentCalendarData->getYear() && $calc[1] == $currentCalendarData->getMonth())
				$printer = new CalendarPrinter($calc[0], $calc[1], $currentCalendarData->getDay());
			else 
				$printer = new CalendarPrinter($calc[0], $calc[1]);
?>
				<td>
<?php		$printer->printCalendar() ?>
				</td>
				
<?php 		if(($calendarLoopStart - $i + 2) % 3 == 0){ ?>
			</tr>
			<tr>
<?php }?>
			
<?php 	} ?>
		</table>
	</body>
</html>