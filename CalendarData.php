<?php
class CalendarData{
	protected $year;
	protected $month;
	protected $day;
	protected $weekDay;
	
// 	Y-m
	public function __construct($dateString){
		list($this->year, $this->month) = explode("-", $dateString);
	}
	public function getYear(){
		return $this->year;
	}
	public function getMonth(){
		return $this->month;
	}
// 	配列で年と月を返す
	public function getCalcMonth($num){
		if ($num > 0)
			$cal = date("Y-m", strtotime($this->year."-".$this->month." + ".$num." month"));
		else 
			$cal = date("Y-m", strtotime($this->year."-".$this->month." ".$num." month"));
	
		return explode("-", $cal);
	}
}
?>