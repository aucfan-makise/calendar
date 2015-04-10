<?php
require "PublicHolidayRequester.php";

class CalendarPrinter{
	private $year;
	private $month;
	private $today;
	private $lastDay;
	private $nextMonthDay = 0;
	private $beforeMonthLastDay;
	
	public function __construct($year, $month, $day = null){
		$this->year = $year;
		$this->month = $month;
		if ($day != null)
			$this->today = (int) $day;
		$this->beforeMonthLastDay = date('d', strtotime($year."-".$month."-"."01 -1 day"));
	}
	
	public function printCalendar(){
		$tableTitle = $this->year."年".$this->month."月";
?>
		<table class="calendar_table">
			<tr>
				<td class="calendar_table_title" colspan="7">
					<?php echo $tableTitle ?>
				</td>
			</tr>
			<tr class="calendar_week_row">
				<td class="calendar_week_column">Sun</td>
				<td class="calendar_week_column">Mon</td>
				<td class="calendar_week_column">The</td>
				<td class="calendar_week_column">Wed</td>
				<td class="calendar_week_column">Thu</td>
				<td class="calendar_week_column">Fri</td>
				<td class="calendar_week_column">Sat</td>
			</tr>
<?php	
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
?>
		</table>
<?php 
	}
	
	private function printDayRow($start, $end){
		$publicHolidayData = new PublicHolidayRequester($this->year, $this->month);
?>
			<tr class="calendar_day_row">
<?php	for ($i = $start; $i <= $end; ++$i){ ?>
<?php		if ($i === $this->today) {?>
				<td class="calendar_today_column">
<?php		} elseif ($publicHolidayData->isHoliday($i)) {?>
				<td class="calendar_public_holiday_column">
<?php		} elseif ($i === $start) {?>
				<td class="calendar_sunday_column">
<?php		} elseif ($i === $start + 6) {?>
				<td class="calendar_saturday_column">
<?php		} else {?>
				<td class="calendar_day_column">
<?php		}
			if($i > 0 && $i <= $this->lastDay)
				echo $i;
			elseif ($i > $this->lastDay){
				$this->nextMonthDayNum++;
				echo $this->nextMonthDayNum;
			}
			elseif ($i <= 0)
				echo $this->beforeMonthLastDay + $i;
			
			if($publicHolidayData->isHoliday($i))
				echo " ".$publicHolidayData->getHolidayName($i);
?>
				</td>
<?php	} ?>
			</tr>
<?php 
		$this->printMessageColumn();		
	}
	
	private function printMessageColumn(){
?>
			<tr>
<?php for ($i = 0; $i < 7; ++$i) {?>
				<td class="calendar_nomal_column">test</td>
<?php }?>
			</tr>
<?php }

	private function getFirstWeekDay(){
		return date("w", strtotime($this->year."-".$this->month."-01"));
	}
	
	private function getLastDay(){
		return date("t", strtotime($this->year."-".$this->month."-01"));
	}
}
?>