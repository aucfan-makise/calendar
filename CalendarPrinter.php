<?php
class CalendarPrinter{
	private $year;
	private $month;
	private $today;
	private $lastDay;
	
	public function __construct($year, $month, $day = null){
		$this->year = $year;
		$this->month = $month;
		if ($day != null)
			$this->today = (int) $day;
	}
	
	public function printCalendar(){
		echo '
			<table class="calendar_table">
			<tr><td class="calendar_table_title" colspan="7">'.
			$this->year."年".$this->month."月".'</td></tr>
			<tr class="calendar_week_row">
			<td class="calendar_week_column">Sun</td>
			<td class="calendar_week_column">Mon</td>
			<td class="calendar_week_column">The</td>
			<td class="calendar_week_column">Wed</td>
			<td class="calendar_week_column">Thu</td>
			<td class="calendar_week_column">Fri</td>
			<td class="calendar_week_column">Sat</td>
			</tr>';
		
// 		月の日の開始の位置
		$firstWeekDay = $this->getFirstWeekDay($year, $month);
		$firstColumnNum = 1 - $firstWeekDay;
		$this->lastDay = $this->getLastDay($year, $month);
		for ($row = 0; $row < 6; ++$row){
			$this->printDayRow($firstColumnNum, $firstColumnNum + 6);
			$firstColumnNum += 7;
		}

		$this->printEndCalendar();
	}
	
	private function printEndCalendar(){
		echo '</table>';
	}
	
	private function printDayRow($start, $end){
		echo '<tr class="calendar_day_row">';
		for ($i = $start; $i <= $end; ++$i){
			if ($i === $this->today)
				echo '<td class="calendar_today_column">';
			elseif ($i === $start)
				echo '<td class="calendar_sunday_column">';
			elseif ($i === $start + 6)
				echo '<td class="calendar_saturday_column">';
			else
				echo '<td class="calendar_day_column">';
			if($i > 0 && $i <= $this->lastDay)
				echo $i;
			echo '</td>';
		}
		echo	'</tr>';
		$this->printMessageColumn();		
	}
	private function printMessageColumn(){
		echo '<tr>';
		for ($i = 0; $i < 7; ++$i)
			echo '<td class="calendar_nomal_column">test</td>';
		echo '</tr>';
	}
	private function getFirstWeekDay(){
		return date("w", strtotime($this->year."-".$this->month."-01"));
	}
	
	private function getLastDay(){
		return date("t", strtotime($this->year."-".$this->month."-01"));
	}
}
?>