<?php
class CalendarFunction{
	const SERVER = "http://calendar-service.net/cal";
	const FIXED_OPTION = "year_style=normal&month_style=numeric&wday_style=none&format=xml&holiday_only=1";
	const YEAR_START_OPTION_NAME = "start_year";
	const YEAR_END_OPTION_NAME = "end_year";
	const MONTH_START_OPTION_NAME = "start_mon";
	const MONTH_END_OPTION_NAME = "end_mon";
	
// 	今日の日付の配列
// 	年　月　日
	private $today;
	private $calendar_array;
	public function __construct($start_year, $start_month, $end_year, $end_month){
		$this->today = explode("-", date("Y-n-d"));
		$this->calendar_array = $this->createCarendarArray($start_year, $start_month, $end_year, $end_month);
	}
	
//     今日かどうかを調べる
	public function isToday($year, $month, $day) {
		return strtotime(implode("-", array($year, $month, $day))) === strtotime(implode("-", $this->today)) ? true : false;
	}
	
// 	祝日かどうかを調べる
	public function isHoliday($year, $month, $day){
		return empty($this->calendar_array[$year][$month][$day]["holiday_name"]) ? false : true;
	}
	
// 	祝日名を返す
    public function getHolidayName($year, $month, $day){
    	return $this->calendar_array[$year][$month][$day]["holiday_name"];
    }
	
// 	土曜日かどうかを調べる
    public function isSaturday($year, $month, $day){
    	return $this->calendar_array[$year][$month][$day]["week_day"] == 6 ? true : false;
    }
    
//     日曜日かどうかを調べる
    public function isSunday($year, $month, $day){
    	return $this->calendar_array[$year][$month][$day]["week_day"] == 0 ? true : false;
    }
    
// 	引数は取得する祝日のはじめの年、月、終わりの年、月
// 	xmlをパースしたものを返す
	private function getPublicHolidayData($start_year, $start_month, $end_year, $end_month){
        $year_start_option = self::YEAR_START_OPTION_NAME."=".$start_year;
        $year_end_option = self::YEAR_END_OPTION_NAME."=".$end_year;
        $month_start_option = self::MONTH_START_OPTION_NAME."=".$start_month;
        $month_end_option = self::MONTH_END_OPTION_NAME."=".$end_month;
        $url = self::SERVER."?".$year_start_option."&".$month_start_option."&".$year_end_option."&".$month_end_option."&".self::FIXED_OPTION;
        $res = null;
        try {
        	$res = simplexml_load_file($url);
        } catch (Exception $e){
//         	TODO:エラーメッセージ
        }
        return $res;
	}
	/*
	 * 
	 */
// 	引数は表示するカレンダーの最初と最後の年月 
//     カレンダーの配列を返す
//     最初の月−１から最後の月＋１で作成
//     year => month => day => "weekday" =>
//                             "..." =>
	private function createCarendarArray($start_year, $start_month, $end_year, $end_month){
		$array = array();
// 		指定された最初の月と最後の月の範囲を広げる
		$start = date("Y-n", strtotime($start_year."-".$start_month." - 1 month"));
		list($start_year, $start_month) = explode("-", $start);
		$end = date("Y-n", strtotime($end_year."-".$end_month." + 1 month"));
		list($end_year, $end_month) = explode("-", $end);
		
		$public_holiday_array = $this->getPublicHolidayData($start_year, $start_month, $end_year, $end_month);
		for($year = $start_year; $year <= $end_year; ++$year){
			if ($year == $start_year && $year == $end_year){
				$array[$year] = $this->createYearCarendarArray($year, $start_month, $end_month, $public_holiday_array);
			} elseif ($year == $start_year && $year < $end_year) {
				$array[$year] = $this->createYearCarendarArray($year, $start_month, 12, $public_holiday_array);
			} elseif ($year > $start_year && $year < $end_year) {
				$array[$year] = $this->createYearCarendarArray($year, 1, 12, $public_holiday_array);
			} elseif ($year > $start_year && $year == $end_year) {
				$array[$year] = $this->createYearCarendarArray($year, 1, $end_month, $public_holiday_array);
			}
		}
		
		return $array;
	}
	
// 	引数は生成するカレンダーの年、最初の月、最後の月、祝日のデータ
// 	一年分位内のカレンダーの配列を生成する
	private function createYearCarendarArray($year, $start_month, $end_month, $public_holiday_array){
		$outer_array = array();
		for($month = $start_month; $month <= $end_month; ++$month){
			$inner_array = array();
			$day_array = array();
			for($day = 1; $day <= date("t", strtotime($year."-".$month)); ++$day){
			    $array['week_day'] = (string)date("w", strtotime($year."-".$month."-".$day));
			    $holiday_name = "";
			    foreach ($public_holiday_array->response->month as $month_array){
			        if ($month_array->attributes()->year == $year && $month_array->attributes()->month == $month){
			            foreach ($month_array->mday as $day_array){
			                if ($day_array->attributes()->mday == $day){
			                    $holiday_name = (string)$day_array['holiday_name'];
			                    break 2;
			                }
			            }
			        }
			    }
			    $array['holiday_name'] = $holiday_name;
			    $inner_array[$day] = $array;
			}
			$outer_array[$month] = $inner_array;	
		}
		return $outer_array;
	}
	
// 	カレンダーの配列のgetter
	public function getCarendarArray(){
		return $this->calendar_array;
	}
	
// 	引数は指定された年月 Y-m
// 	コンボボックスに表示する年月の配列を返す
//  前後１０ヶ月
//  Y-m => Y年m月
	public function getComboBoxArray(){
		$year_month = $this->today[0]."-".$this->today[1];
		$array = array();
        for ($i = -10; $i <=10; ++$i){
        	$array[date("Y-n", strtotime($year_month." ".$i." month"))] = date("Y年n月", strtotime($year_month." ".$i." month")); 
        }
        return $array;
	}
	
	private static function getLastDay($date){
		$next_month = date("Y-m", strtotime($date." +1 month"));
		return date("d", strtotime($next_month." -1 day"));
	}
	
// 	引数はY-n
//     1ヶ月先の年と月を連想配列で返す
//     year =>  , month =>
	public static function getNextMonth($date){
		$next_date = date("Y-n", strtotime($date." +1 month"));
		list($year, $month) = explode("-", $next_date);
		$array = array("year" => $year, "month" => $month);
		return $array;
	}
	
// 	引数はY-n
// 	1ヶ月前の年と月を連想配列で返す
	public static function getBeforeMonth($date){
		$before_date = date("Y-n", strtotime($date." -1 month"));
		list($year, $month) = explode("-", $before_date);
		$array = array("year" => $year, "month" => $month);
		return $array;
	}
}
?>