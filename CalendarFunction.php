<?php
class CalendarFunction {
    const SERVER = "http://calendar-service.net/cal";
    const FIXED_OPTION = "year_style=normal&month_style=numeric&wday_style=none&format=xml&holiday_only=1";
    const YEAR_START_OPTION_NAME = "start_year";
    const YEAR_END_OPTION_NAME = "end_year";
    const MONTH_START_OPTION_NAME = "start_mon";
    const MONTH_END_OPTION_NAME = "end_mon";
    
    private static $calendar_td = array( 
        "today" => "calendar_today_column",
        "default" => "calendar_day_column",
        "0" => "calendar_sunday_column",
        "6" => "calendar_saturday_column",
        "public_holiday" => "calendar_public_holiday_column"
    );
    
    // 今日の日付の配列
    // 年　月　日
    private $today;
//     カレンダーの配列
    private $calendar_array;
//     選択されたカレンダーの年月の配列　年　月　日
    private $selected_date;
    
    private $start_calendar;
    private $end_calendar;
//     表示するカレンダーの数
    private $calendar_size;
    
    public function __construct($selected_date, $calendar_size){
    	$this->today = explode("-", date("Y-n-d"));
    	$this->selected_date = is_null($selected_date) ? $this->today[0]."-".$this->today[1] : $selected_date; 
    	$this->calendar_size = is_null($calendar_size) || ! ctype_digit($calendar_size) ? 3 : $calendar_size;
    	$this->initialize();
    }
    
    /**
     * 表示するカレンダーの計算などを行う
     */
    private function initialize(){
    	$this->start_calendar = date("Y-n", strtotime($this->selected_date." -".floor($this->calendar_size / 2)." month"));
    	list($start_year, $start_month) = explode("-", $this->start_calendar);
    	$this->end_calendar = date("Y-n", strtotime($this->selected_date." +".ceil($this->calendar_size / 2)." month -1 month"));
        list($end_year, $end_month) = explode("-", $this->end_calendar);
        $this->calendar_array = $this->createCarendarArray($start_year, $start_month, $end_year, $end_month);
    }

    /**
     * 選択されたカレンダーの年月の配列を返す
     */
    public function getSelectedCalendar(){
    	return $this->selected_date;
    }

    /**
     * 選択されたカレンダーかどうか確認する
     */
    public function isSelectedCalendar($key){
    	return $this->selected_date == $key ? true : false;
    }

    /**
     * 表示するカレンダーの数を返す
     */
    public function getCalendarSize(){
    	return $this->calendar_size;
    }

    /**
     * 今日かどうかを調べる
     */
    public function isToday($year, $month, $day) {
        return strtotime ( implode ( "-", array (
                $year,
                $month,
                $day 
        ) ) ) === strtotime ( implode ( "-", $this->today ) ) ? true : false;
    }

    /**
     * 祝日かどうかを調べる
     */
    public static function isHoliday($day) {
        return empty ( $day ["holiday_name"] ) ? false : true;
    }

    /**
     * 祝日名を返す
     */
    public static function getHolidayName($day) {
        return $day ["holiday_name"];
    }

    /**
     * 土曜日かどうかを調べる
     */
    public static function isSaturday($day) {
        return $day ["week_day"] == 6 ? true : false;
    }

    /**
     * 日曜日かどうかを調べる
     */
    public static function isSunday($day) {
        return $day ["week_day"] == 0 ? true : false;
    }

    /**
     * 引数は取得する祝日のはじめの年、月、終わりの年、月
     * xmlをパースしたものを返す
     */
    private function getPublicHolidayData($start_year, $start_month, $end_year, $end_month) {
        $year_start_option = self::YEAR_START_OPTION_NAME . "=" . $start_year;
        $year_end_option = self::YEAR_END_OPTION_NAME . "=" . $end_year;
        $month_start_option = self::MONTH_START_OPTION_NAME . "=" . $start_month;
        $month_end_option = self::MONTH_END_OPTION_NAME . "=" . $end_month;
        $url = self::SERVER . "?" . $year_start_option . "&" . $month_start_option . "&" . $year_end_option . "&" . $month_end_option . "&" . self::FIXED_OPTION;
        $res = null;
        try {
            $res = simplexml_load_file ( $url );
        } catch ( Exception $e ) {
            echo "<pre>祝日が読み込めませんでした</pre>";
        }
        return $res;
    }
    
    /**
     *   引数は表示するカレンダーの最初と最後の年月
     *   カレンダーの配列を返す
     *   最初の月−１から最後の月＋１で作成
     *   year => month => day => "weekday" =>
     *   "..." =>
     */
    private function createCarendarArray($start_year, $start_month, $end_year, $end_month) {
        $array = array ();
        // 指定された最初の月と最後の月の範囲を広げる
        $start = date ( "Y-n", strtotime ( $start_year . "-" . $start_month . " - 1 month" ) );
        list ( $start_year, $start_month ) = explode ( "-", $start );
        $end = date ( "Y-n", strtotime ( $end_year . "-" . $end_month . " + 1 month" ) );
        list ( $end_year, $end_month ) = explode ( "-", $end );
        
        $public_holiday_array = $this->getPublicHolidayData ( $start_year, $start_month, $end_year, $end_month );
        for($year = $start_year; $year <= $end_year; ++ $year) {
            if ($year == $start_year && $year == $end_year) {
                $array [$year] = $this->createYearCarendarArray ( $year, $start_month, $end_month, $public_holiday_array );
            } elseif ($year == $start_year && $year < $end_year) {
                $array [$year] = $this->createYearCarendarArray ( $year, $start_month, 12, $public_holiday_array );
            } elseif ($year > $start_year && $year < $end_year) {
                $array [$year] = $this->createYearCarendarArray ( $year, 1, 12, $public_holiday_array );
            } elseif ($year > $start_year && $year == $end_year) {
                $array [$year] = $this->createYearCarendarArray ( $year, 1, $end_month, $public_holiday_array );
            }
        }
        
        return $array;
    }

    /**
     * 引数は生成するカレンダーの年、最初の月、最後の月、祝日のデータ
     * 一年分位内のカレンダーの配列を生成する
     */
    private function createYearCarendarArray($year, $start_month, $end_month, $public_holiday_array) {
        $outer_array = array ();
        for($month = $start_month; $month <= $end_month; ++ $month) {
            $inner_array = array ();
            $day_array = array ();
            for($day = 1; $day <= date ( "t", strtotime ( $year . "-" . $month ) ); ++ $day) {
                $array ['week_day'] = ( string ) date ( "w", strtotime ( $year . "-" . $month . "-" . $day ) );
                $array ['day'] = $day;
                if($day == $this->today[2] && $month == $this->today[1] && $year == $this->today[0]){
                    $array["td_class"] = self::$calendar_td["today"];
                }elseif($array["week_day"] == 0){
                    $array["td_class"] = self::$calendar_td["0"];
                }elseif ($array["week_day"] == 6){
                    $array["td_class"] = self::$calendar_td["6"];
                }else{
                    $array["td_class"] = self::$calendar_td["default"];
                }
                $holiday_name = "";
                foreach ( $public_holiday_array->response->month as $month_array ) {
                    if ($month_array->attributes ()->year == $year && $month_array->attributes ()->month == $month) {
                        foreach ( $month_array->mday as $day_array ) {
                            if ($day_array->attributes ()->mday == $day) {
                                $holiday_name = ( string ) $day_array ['holiday_name'];
                                $array['td_class'] = self::$calendar_td["public_holiday"];
                                break 2;
                            }
                        }
                    }
                }
                $array ['holiday_name'] = $holiday_name;
                $inner_array [$day] = $array;
                $inner_array ["last_day"] =& end($inner_array);
                $inner_array ["in_range"] = $this->isInRange($year."-".$month) ? true : false;
            }
            $outer_array [$month] = $inner_array;
        }
        return $outer_array;
    }

    /**
     * 表示する範囲内かどうか調べる
     * @param unknown $date
     * @return boolean
     */
    private function isInRange($date){
    	return strtotime($this->start_calendar) <= strtotime($date) && strtotime($this->end_calendar) >= strtotime($date) ? true : false;
    }
    
    /**
     * 一月の表示用のカレンダーの配列を返す
     * @param unknown $year
     * @param unknown $month
     * @return multitype:NULL unknown
     */
    public function getMonthCalendarArray($year, $month){
    	$array = array();
//     	前月
    	if ($this->calendar_array[$year][$month][1]["week_day"] != 0){
        	$before_month = $this->getBeforeMonth($year."-".$month);
        	$before_month_end_day = date("t", strtotime($before_month["year"]."-".$before_month["month"]));
        	$before_month_start_day = $before_month_end_day - $this->calendar_array[$year][$month][1]["week_day"] + 1;
        	foreach (range($before_month_start_day, $before_month_end_day) as $day){
        		$array[] = $this->calendar_array[$before_month["year"]][$before_month["month"]][$day];
        	}
    	}
//     	今月
        foreach ($this->calendar_array[$year][$month] as $key => $value){
        	if (is_integer($key)) $array[] = $value;
        }
//         次月
        if ($this->calendar_array[$year][$month]["last_day"]["week_day"] != 6){
        	$next_month = $this->getNextMonth($year."-".$month);
        	$next_month_end_day = 6 - $this->calendar_array[$year][$month]["last_day"]["week_day"];
        	foreach (range(1, $next_month_end_day) as $day){
        		$array[] = $this->calendar_array[$next_month["year"]][$next_month["month"]][$day];
        	}
        }
        
        return $array;
    }
    
    /**
     * カレンダーの配列のgetter
     * @return multitype:NULL multitype:multitype:mixed boolean string
     */
    public function getCalendarArray() {
        return $this->calendar_array;
    }

    /**引数は指定された年月 Y-m
     * コンボボックスに表示する年月の配列を返す
     * 前後１０ヶ月
     * Y-m => Y年m月
     * @return multitype:string
     */
    public function getComboBoxArray() {
        $year_month = $this->today [0] . "-" . $this->today [1];
        $array = array ();
        for($i = - 10; $i <= 10; ++ $i) {
            $array [date ( "Y-n", strtotime ( $year_month . " " . $i . " month" ) )] = date ( "Y年n月", strtotime ( $year_month . " " . $i . " month" ) );
        }
        return $array;
    }
    
    /**
     * 最後の日にちを取得
     * @param unknown $date
     * @return string
     */
    private static function getLastDay($date) {
        $next_month = date ( "Y-m", strtotime ( $date . " +1 month" ) );
        return date ( "d", strtotime ( $next_month . " -1 day" ) );
    }
    
    /**
     * 引数はY-n
     * 1ヶ月先の年と月を連想配列で返す
     * year => , month =>
     */
    public static function getNextMonth($date) {
        $next_date = date ( "Y-n", strtotime ( $date . " +1 month" ) );
        list ( $year, $month ) = explode ( "-", $next_date );
        $array = array (
                "year" => $year,
                "month" => $month 
        );
        return $array;
    }
    
   
    /**
     * 引数はY-n
     * 1ヶ月前の年と月を連想配列で返す
     * @param unknown $date
     * @return multitype:unknown multitype:
     */
    public static function getBeforeMonth($date) {
        $before_date = date ( "Y-n", strtotime ( $date . " -1 month" ) );
        list ( $year, $month ) = explode ( "-", $before_date );
        $array = array (
                "year" => $year,
                "month" => $month 
        );
        return $array;
    }
}
?>