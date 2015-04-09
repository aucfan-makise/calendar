<?php
class CalenderData{
	private $thisYear;
	private $thisMonth;
	private $thisDay;
	private $thisWeekDay;
	
// 	[末日、一日の曜日]の配列
	private $beforeMonthData;
	private $thisMonthData;
	private $afterMonthData;
	
// 	Y-m-d-wを引数
	public function __construct() {
// test
		$date = date('Y-m-d-w');
// 		echo $date;
		list($thisYear, $thisMonth, $thisDay, $thisWeekDay) = explode("-", $date);
	
		$beforeMonthData = $this->getLastDay($thisYear, $thisMonth - 1);
		$thisMonthData = $this->getLastDay($thisYear, $thisMonth);
		$afterMonthData = $this->getLastDay($thisYear, $thisMonth + 1);
		var_dump($beforeMonthData);
	}
	
// 	年、月を引数　末日,1日の曜日の配列を返す
	private function getLastDay($year, $month){
		$lastDay = date("t-w", strtotime($year."-".$month."-01"));
		$lastDay = explode("-", $lastDay);
		return $lastDay;
	}
	
	public function getBeforeMonthData(){
		return $this->beforeMonthData;
	}
	public function getThisMonthData(){
		return $this->thisMonthData;
	}
	public function getAfterMonthData(){
		return $this->afterMonthData;
	}
}
?>