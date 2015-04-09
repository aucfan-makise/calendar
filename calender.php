<html>
	<head>
		<title>Calender</title>
		<link rel="stylesheet" type="text/css" href="./calender.css">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	</head>
	<body>
<?php
require "CalenderData.php";
require "CalenderPrinter.php";

	$printer = new CalenderPrinter();
	$printer->printCalender('2015', '4');
	$date = date('Y-m-d-w');
	$calenderData = new CalenderData();
?>
	</body>
</html>