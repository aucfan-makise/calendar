<?php
class CalenderPrinter{
	private $lastDay;
	
	public function printCalender($year, $month){
		echo '
			<table class="calender_table">
			<tr><td class="calender_table_title" colspan="7">'.
			$year."年".$month."月".'</td></tr>
			<tr class="calender_week_row">
			<td class="calender_week_column">Sun</td>
			<td class="calender_week_column">Mon</td>
			<td class="calender_week_column">The</td>
			<td class="calender_week_column">Wed</td>
			<td class="calender_week_column">Thu</td>
			<td class="calender_week_column">Fri</td>
			<td class="calender_week_column">Sat</td>
			</tr>';
		
// 		月の日の開始の位置
		$firstWeekDay = $this->getFirstWeekDay($year, $month);
		$firstColumnNum = 1 - $firstWeekDay;
		$this->lastDay = $this->getLastDay($year, $month);
		for ($row = 0; $row < 5; ++$row){
			$this->printDayRow($firstColumnNum, $firstColumnNum + 6);
			$firstColumnNum += 7;
		}

		$this->printEndCalender();
		echo $this->getLastDay($year, $month);
		echo $this->getFirstWeekDay($year, $month);
	}
	
	private function printEndCalender(){
		echo '</table>';
	}
	
	private function printDayRow($start, $end){
		echo '<tr class="calender_day_row">';
		for ($i = $start; $i <= $end; ++$i){
			echo '<td class="calender_day_column">';
			if($i > 0 && $i < $this->lastDay)
				echo $i;
			echo '</td>';
		}
		echo	'</tr>';
		$this->printMessageColumn();		
	}
	private function printMessageColumn(){
		echo '<tr>';
		for ($i = 0; $i < 7; ++$i)
			echo '<td class="calender_nomal_column">test</td>';
		echo '</tr>';
	}
	private function getFirstWeekDay($year, $month){
		return date("w", strtotime($year."-".$month."-01"));
	}
	
	private function getLastDay($year, $month){
		return date("t", strtotime($year."-".$month."01"));
	}
}
?>