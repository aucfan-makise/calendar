<?php
class CalendarFunction {
    const SERVER = "http://calendar-service.net/cal";
    const FIXED_OPTION = "year_style=normal&month_style=numeric&wday_style=none&format=xml&holiday_only=1";
    const YEAR_START_OPTION_NAME = "start_year";
    const YEAR_END_OPTION_NAME = "end_year";
    const MONTH_START_OPTION_NAME = "start_mon";
    const MONTH_END_OPTION_NAME = "end_mon";
    
    const AUCTOPIC = "http://aucfan.com/article/feed/";

    const DATABASE_NAME = "schedule";
    const TABLE_NAME = "my_schedules";
    const DATABASE_USER_NAME = "makise";
    
    private static $calendar_div = array(
            "today" => "calendar_today_div",
            "default" => "calendar_date_div",
            "0" => "calendar_sunday_div",
            "6" => "calendar_saturday_div",
            "public_holiday" => "calendar_public_holiday_div"
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
    
//     祝日のデータ
    private $public_holiday_array;
//     オークショントピックのデータ
    private $auction_topic_array;
    
    private $connection;
    
    public function connectionTest(){
        $link = mysql_connect("localhost", self::DATABASE_USER_NAME, "");
        if (! $link){
            die("失敗");
        }
        print("<pre>成功</pre>");
        
        $close_flag = mysql_close($link);
        if($close_flag){
            print("<pre>成功</pre>");
        }
    }
    
    public function isTodaysYear($year){
        return $this->today[0] == $year ? true : false;
    }
    public function isTodaysMonth($month){
        return $this->today[1] == $month ? true : false;
    }
    public function isTodaysDay($day){
        return $this->today[2] == $day ? true : false;
    }
    
    private function insertSchedule($user_title, $user_detail, $user_start_time, $user_end_time){
        try {
            $stmt = $this->connection->prepare("INSERT INTO my_schedules (title, detail, start_time, end_time, created_at, update_at) values(:title, :detail, :start_time, :end_time, now(), now())");
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(":detail", $detail);
            $stmt->bindParam(":start_time", $start_time);
            $stmt->bindParam(":end_time", $end_time);
            $title = $user_title;
            $detail = $user_detail;
            $start_time = $user_start_time;
            $end_time = $user_end_time;
            $stmt->execute();
            $stmt = null;
        } catch (PDOException $e){
            echo "<pre> エラー:".$e->getMessage()."</pre>";
            die();
        }
    }
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
        
        $this->connection = new PDO('mysql:host=localhost;dbname='.self::DATABASE_NAME, self::DATABASE_USER_NAME, "");
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
    
    private function getAucfanTopicData($start_date){
        $topics_array = array();
        foreach (range(1, 36) as $page){
            $topic = array();
            $url = self::AUCTOPIC."?paged=".$page;
            $res = null;
            try {
                $res = simplexml_load_file($url);
            } catch ( Exception $e){
                echo "<pre>オークショントピックが読み込めませんでした<pre>";
            }
            foreach ($res->channel->item as $topics){
                if (strtotime($this->end_calendar) <= strtotime($topic_array["time"])) break 2;
    //             0:year 1:month 2:day 3:hour 4:minute 5:second
                $time = explode("-", date("Y-n-d-H-i-s", strtotime($topics->pubDate)));
                
                if (! in_array($time[0], $topics_array)) $topics_array[$time[0]][] = array();
                if (! in_array($time[1], $topics_array[$time[0]])) $topics_array[$time[0]][$time[1]][] = array();
                if (! in_array($time[2], $topics_array[$time[0]][$time[1]])) $topics_array[$time[0]][$time[1]][$time[2]][] = array();
                
                $topic_array = array();
                $topic_array["time"] = $time[3]."-".$time[4]."-".$time[5];
                $topic_array["title"] = (string) $topics->title;
                $topic_array["link"] = (string) $topics->link;
                $topics_array[$time[0]][$time[1]][$time[2]][] = $topic_array;
            }
        }
        return $topics_array;
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
        
        $this->public_holiday_array = $this->getPublicHolidayData ( $start_year, $start_month, $end_year, $end_month );
        $this->auction_topic_array = $this->getAucfanTopicData($this->start_calendar);
        for($year = $start_year; $year <= $end_year; ++ $year) {
            if ($year == $start_year && $year == $end_year) {
                $array [$year] = $this->createYearCarendarArray ( $year, $start_month, $end_month );
            } elseif ($year == $start_year && $year < $end_year) {
                $array [$year] = $this->createYearCarendarArray ( $year, $start_month, 12 );
            } elseif ($year > $start_year && $year < $end_year) {
                $array [$year] = $this->createYearCarendarArray ( $year, 1, 12 );
            } elseif ($year > $start_year && $year == $end_year) {
                $array [$year] = $this->createYearCarendarArray ( $year, 1, $end_month);
            }
        }
        
        return $array;
    }

    /**
     * 引数は生成するカレンダーの年、最初の月、最後の月、祝日のデータ
     * 一年分位内のカレンダーの配列を生成する
     */
    private function createYearCarendarArray($year, $start_month, $end_month) {
        $outer_array = array ();
        for($month = $start_month; $month <= $end_month; ++ $month) {
            $inner_array = array ();
            $day_array = array ();
            for($day = 1; $day <= date ( "t", strtotime ( $year . "-" . $month ) ); ++ $day) {
                $array ['week_day'] = ( string ) date ( "w", strtotime ( $year . "-" . $month . "-" . $day ) );
                $array ['day'] = $day;
                if($day == $this->today[2] && $month == $this->today[1] && $year == $this->today[0]){
                    $array["div_class"] = self::$calendar_div["today"];
                }elseif($array["week_day"] == 0){
                    $array["div_class"] = self::$calendar_div["0"];
                }elseif ($array["week_day"] == 6){
                    $array["div_class"] = self::$calendar_div["6"];
                }else{
                    $array["div_class"] = self::$calendar_div["default"];
                }
                $holiday_name = "";
                foreach ( $this->public_holiday_array->response->month as $month_array ) {
                    if ($month_array->attributes ()->year == $year && $month_array->attributes ()->month == $month) {
                        foreach ( $month_array->mday as $day_array ) {
                            if ($day_array->attributes ()->mday == $day) {
                                $holiday_name = ( string ) $day_array ['holiday_name'];
                                $array["div_class"] = self::$calendar_div["public_holiday"];
                                break 2;
                            }
                        }
                    }
                }
                $array ['holiday_name'] = $holiday_name;
                $aucfan_topics = array();
                if (! is_null($this->auction_topic_array[$year][$month][$day])){
                    foreach($this->auction_topic_array[$year][$month][$day] as $topic){
                        if(empty($topic)) continue;
                        
                        $aucfan_topics[] = $topic;
                    }
                }
                $array['aucfan_topic'] = $aucfan_topics;
                $inner_array [$day] = $array;
                $inner_array ["last_day"] =& end($inner_array);
                $inner_array ["in_range"] = $this->inRange($year."-".$month) ? true : false;
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
    private function inRange($date){
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
    
    public function getScheduleYear() {
        $array = array();
        foreach (range(0, 3) as $year) $array[] = $this->today[0] + $year;
        
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