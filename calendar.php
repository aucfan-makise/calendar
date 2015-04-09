<html>
	<head>
		<title>Calender</title>
		<link rel="stylesheet" type="text/css" href="./calendar.css">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	</head>
	<body>
	
	<?php 
	require "CalendarData.php";
	require "CurrentCalendarData.php";
	require "CalendarPrinter.php";
		$currentCalendarData = new CurrentCalendarData();
		$centerCalendarData = new CurrentCalendarData();
		
		if($_GET["pre"] != null)
			$centerCalendarData = new CalendarData($_GET["pre"]);
		elseif($_GET["next"] != null)
			$centerCalendarData = new CalendarData($_GET["next"]);
		else
			$centerCalendarData = new CalendarData($currentCalendarData->getYear()."-".$currentCalendarData->getMonth());

		echo '
			<form method="get" action="calendar.php">
				<button name="pre" value="'.date("Y-m", strtotime($centerCalendarData->getYear()."-".$centerCalendarData->getMonth()." -1 month")).'">前</button>
				<button name="next" value="'.date("Y-m", strtotime($centerCalendarData->getYear()."-".$centerCalendarData->getMonth()." +1 month")).'">次</button>
			</form>';

		echo '<table border="0"><tr>';
		for ($i = -1; $i < 2; ++$i){
			$calc = $centerCalendarData->getCalcMonth($i);
			if ($calc[0] == $currentCalendarData->getYear() && $calc[1] == $currentCalendarData->getMonth())
				$printer = new CalendarPrinter($calc[0], $calc[1], $currentCalendarData->getDay());
			else 
				$printer = new CalendarPrinter($calc[0], $calc[1]);
			echo '<td>';
			$printer->printCalendar();
			echo '</td>';
		}
		echo '</table>'
?>
	</body>
</html>