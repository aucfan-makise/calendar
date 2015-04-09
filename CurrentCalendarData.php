<?php
// 現在の日付を基にしたデータ
class CurrentCalendarData extends CalendarData{
	protected $day;
	protected $weekDay;
	
	public function __construct() {
		$date = date('Y-m-d-w');
		list($this->year, $this->month, $this->day, $this->weekDay) = explode("-", $date);
	}
	
	public function getDay() {
		return $this->day;
	}
	public function getWeekDay() {
		return $this->weekDay;
	}
}
?>