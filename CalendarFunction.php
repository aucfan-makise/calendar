<?php
require_once "Properties.php";
require_once "ScheduleFunction.php";
class CalendarFunction {
    private $error_message;
    private $schedule_function;
    private static $calendar_div = array(
            "today" => "calendar_today_div",
            "default" => "calendar_date_div",
            "0" => "calendar_sunday_div",
            "6" => "calendar_saturday_div",
            "public_holiday" => "calendar_public_holiday_div" 
    );
    private $todays_datetime;
    private $todays_date_array;
    
    // カレンダーの配列
    private $calendar_array;
    private $selected_date_datetime;
    private $start_calendar_datetime;
    private $start_calendar;
    private $end_calendar;
    // 表示するカレンダーの数
    private $calendar_size;
    
    // 祝日のデータ
    private $public_holiday_array;
    // オークショントピックのデータ
    private $auction_topic_array;
    private $schedules_array;
    private $view_id;
    
    /**
     * 読み込んでいる予定のidを取得する
     * 
     * @access public
     * @return string
     */
    public function getViewId() {
        return $this->view_id;
    }
    
    /**
     * 予定を読み出す状態にあるかどうかを調べる
     * 
     * @access public
     * @return boolean
     */
    public function isViewMode() {
        return is_null($this->view_id) ? false : true;
    }
    
    /**
     *
     * @access public
     * @param array $get_data            
     * @param array $post_data            
     */
    public function __construct($get_data = array(), $post_data = array()) {
        $this->schedule_function = new ScheduleFunction($post_data);
        $this->view_id = $get_data["view_id"];
        $this->calendar_size = is_null($calendar_size) || ! ctype_digit($calendar_size) ? 3 : $calendar_size;
        
        $this->initialize($get_data);
    }
    
    /**
     * 初期化とか
     * 
     * @access private
     * @param array $get_data            
     */
    private function initialize($get_data) {
        $calendar_size = $get_data["calendar_size"];
        $this->todays_datetime = new DateTime('NOW');
        $this->todays_date_array = date_parse($this->todays_datetime->format('Y-n-j G:i'));
        $this->selected_date_datetime = new DateTime($get_data["selected_date"]);
        $this->start_calendar = date("Y-n", strtotime($this->selected_date_datetime->format('Y-n') . " -" . floor($this->calendar_size / 2) . " month"));
        list($start_year, $start_month) = explode("-", $this->start_calendar);
        $this->end_calendar = date("Y-n", strtotime($this->selected_date_datetime->format('Y-n') . " +" . ceil($this->calendar_size / 2) . " month -1 month"));
        list($end_year, $end_month) = explode("-", $this->end_calendar);
        $this->calendar_array = $this->createCarendarArray($start_year, $start_month, $end_year, $end_month);
    }
    
    /**
     * 今日の年かどうか調べる
     *
     * @param string $year            
     * @return boolean
     */
    public function isTodaysYear($year) {
        return $this->todays_date_array["year"] == $year ? true : false;
    }
    
    /**
     * 今日の月かどうか調べる
     *
     * @param string $month            
     * @return boolean
     */
    public function isTodaysMonth($month) {
        return $this->todays_date_array["month"] == $month ? true : false;
    }
    
    /**
     * 今日の日かどうか調べる
     *
     * @param string $day            
     * @return boolean
     */
    public function isTodaysDay($day) {
        return $this->todays_date_array["day"] == $day ? true : false;
    }
    
    /**
     * 今の時間かどうか調べる
     *
     * @param string $hour            
     * @return boolean
     */
    public function isThisHour($hour) {
        return $this->todays_date_array["hour"] == $hour ? true : false;
    }
    
    /**
     * 今の分かどうか調べる
     *
     * @param string $minute            
     * @return boolean
     */
    public function isThisMinute($minute) {
        return $this->todays_date_array["minute"] == $minute ? true : false;
    }
    
    /**
     * 選択されたカレンダーの年月を返す
     * 
     * @access public
     */
    public function getSelectedCalendar() {
        return $this->selected_date_datetime->format('Y-n');
    }
    
    /**
     * 選択されたカレンダーかどうか確認する
     * 
     * @access public
     * @param string $key            
     * @return boolean
     */
    public function isSelectedCalendar($key) {
        return $this->selected_date_datetime->format('Y-n') == $key ? true : false;
    }
    public function getSelectedDateArray() {
        return date_parse($this->selected_date_datetime->format('Y-n-j'));
    }
    /**
     * 表示するカレンダーの数を返す
     * 
     * @access public
     */
    public function getCalendarSize() {
        return $this->calendar_size;
    }
    
    /**
     * 今日かどうかを調べる
     * 
     * @access public
     */
    public function isToday($year, $month, $day) {
        return strtotime(implode("-", array(
                $year,
                $month,
                $day 
        ))) === strtotime($this->todays_datetime->format("Y-n-j")) ? true : false;
    }
    
    /**
     * 祝日かどうかを調べる
     * 
     * @access public
     */
    public static function isHoliday($day) {
        return empty($day["holiday_name"]) ? false : true;
    }
    
    /**
     * 祝日名を返す
     * 
     * @access public
     */
    public static function getHolidayName($day) {
        return $day["holiday_name"];
    }
    
    /**
     * 土曜日かどうかを調べる
     */
    public static function isSaturday($day) {
        return $day["week_day"] == 6 ? true : false;
    }
    
    /**
     * 日曜日かどうかを調べる
     */
    public static function isSunday($day) {
        return $day["week_day"] == 0 ? true : false;
    }
    
    /**
     * 引数は取得する祝日のはじめの年、月、終わりの年、月
     * xmlをパースしたものを返す
     *
     * @access private
     * @param string $start_year            
     * @param string $start_month            
     * @param string $end_year            
     * @param string $end_month            
     * @return SimpleXMLElement
     */
    private function getPublicHolidayData($start_year, $start_month, $end_year, $end_month) {
        $year_start_option = Properties::YEAR_START_OPTION_NAME . "=" . $start_year;
        $year_end_option = Properties::YEAR_END_OPTION_NAME . "=" . $end_year;
        $month_start_option = Properties::MONTH_START_OPTION_NAME . "=" . $start_month;
        $month_end_option = Properties::MONTH_END_OPTION_NAME . "=" . $end_month;
        $url = Properties::SERVER . "?" . $year_start_option . "&" . $month_start_option . "&" . $year_end_option . "&" . $month_end_option . "&" . Properties::FIXED_OPTION;
        $res = null;
        try {
            $res = simplexml_load_file($url);
        } catch ( Exception $e ) {
            $this->setErrorMessage("祝日が読み込めませんでした。");
        }
        return $res;
    }
    
    /**
     * オークションのトピックを取ってくる
     * 
     * @access private
     * @return Ambigous <multitype:, multitype:string >
     */
    private function getAucfanTopicData() {
        $topics_array = array();
        foreach(range(1, 1) as $page) {
            $topic = array();
            $url = Properties::AUCTOPIC . "?paged=" . $page;
            $res = null;
            try {
                $res = simplexml_load_file($url);
            } catch ( Exception $e ) {
                $this->setErrorMessage("オークショントピックが読み込めませんでした。");
                return array();
            }
            foreach($res->channel->item as $topics) {
                if (strtotime($this->end_calendar) <= strtotime($topic_array["time"]))
                    break 2;
                    // 0:year 1:month 2:day 3:hour 4:minute 5:second
                $time = explode("-", date("Y-n-d-H-i-s", strtotime($topics->pubDate)));
                
                if (! in_array($time[0], $topics_array))
                    $topics_array[$time[0]][] = array();
                if (! in_array($time[1], $topics_array[$time[0]]))
                    $topics_array[$time[0]][$time[1]][] = array();
                if (! in_array($time[2], $topics_array[$time[0]][$time[1]]))
                    $topics_array[$time[0]][$time[1]][$time[2]][] = array();
                
                $topic_array = array();
                $topic_array["time"] = $time[3] . "-" . $time[4] . "-" . $time[5];
                $topic_array["title"] = (string) $topics->title;
                $topic_array["link"] = (string) $topics->link;
                $topics_array[$time[0]][$time[1]][$time[2]][] = $topic_array;
            }
        }
        return $topics_array;
    }
    
    /**
     * 引数は表示するカレンダーの最初と最後の年月
     * カレンダーの配列を返す
     * 最初の月−１から最後の月＋１で作成
     * year => month => day => "weekday" =>
     * "..." =>
     * 
     * @access private
     * @param string $start_year            
     * @param string $start_month            
     * @param string $end_year            
     * @param string $end_month            
     * @return multitype:NULL multitype:multitype:mixed boolean multitype:unknown
     */
    private function createCarendarArray($start_year, $start_month, $end_year, $end_month) {
        $array = array();
        // 指定された最初の月と最後の月の範囲を広げる
        $start = date("Y-n", strtotime($start_year . "-" . $start_month . " - 1 month"));
        $start_datetime = new DateTime($start);
        list($start_year, $start_month) = explode("-", $start);
        $end = date("Y-n", strtotime($end_year . "-" . $end_month . " + 1 month"));
        $end_datetime = new datetime($end);
        list($end_year, $end_month) = explode("-", $end);
        
        $this->public_holiday_array = $this->getPublicHolidayData($start_year, $start_month, $end_year, $end_month);
        $this->auction_topic_array = $this->getAucfanTopicData();
        
        $this->schedule_function->fetchSchedule($start_datetime, $end_datetime);
        $this->schedules_array = $this->schedule_function->getSchedulesArray();
        for($year = $start_year; $year <= $end_year; ++ $year) {
            if ($year == $start_year && $year == $end_year) {
                $array[$year] = $this->createYearCarendarArray($year, $start_month, $end_month);
            } elseif ($year == $start_year && $year < $end_year) {
                $array[$year] = $this->createYearCarendarArray($year, $start_month, 12);
            } elseif ($year > $start_year && $year < $end_year) {
                $array[$year] = $this->createYearCarendarArray($year, 1, 12);
            } elseif ($year > $start_year && $year == $end_year) {
                $array[$year] = $this->createYearCarendarArray($year, 1, $end_month);
            }
        }
        
        return $array;
    }
    
    /**
     * 引数は生成するカレンダーの年、最初の月、最後の月、祝日のデータ
     * 一年分位内のカレンダーの配列を生成する
     * 
     * @access private
     * @param string $year            
     * @param string $start_month            
     * @param string $end_month            
     * @return multitype:multitype:mixed boolean multitype:unknown
     */
    private function createYearCarendarArray($year, $start_month, $end_month) {
        $outer_array = array();
        for($month = $start_month; $month <= $end_month; ++ $month) {
            $inner_array = array();
            $day_array = array();
            for($day = 1; $day <= date("t", strtotime($year . "-" . $month)); ++ $day) {
                $array['schedules'] = $this->schedules_array[$year][$month][$day];
                $array['week_day'] = (string) date("w", strtotime($year . "-" . $month . "-" . $day));
                $array['day'] = $day;
                if ($day == $this->todays_date_array["day"] && $month == $this->todays_date_array["month"] && $year == $this->todays_date_array["year"]) {
                    $array["div_class"] = self::$calendar_div["today"];
                } elseif ($array["week_day"] == 0) {
                    $array["div_class"] = self::$calendar_div["0"];
                } elseif ($array["week_day"] == 6) {
                    $array["div_class"] = self::$calendar_div["6"];
                } else {
                    $array["div_class"] = self::$calendar_div["default"];
                }
                $holiday_name = "";
                foreach($this->public_holiday_array->response->month as $month_array) {
                    if ($month_array->attributes()->year == $year && $month_array->attributes()->month == $month) {
                        foreach($month_array->mday as $day_array) {
                            if ($day_array->attributes()->mday == $day) {
                                $holiday_name = (string) $day_array['holiday_name'];
                                $array["div_class"] = self::$calendar_div["public_holiday"];
                                break 2;
                            }
                        }
                    }
                }
                $array['holiday_name'] = $holiday_name;
                $aucfan_topics = array();
                if (! is_null($this->auction_topic_array[$year][$month][$day])) {
                    foreach($this->auction_topic_array[$year][$month][$day] as $topic) {
                        if (empty($topic))
                            continue;
                        
                        $aucfan_topics[] = $topic;
                    }
                }
                $array['aucfan_topic'] = $aucfan_topics;
                $inner_array[$day] = $array;
                $inner_array["last_day"] = & end($inner_array);
                $inner_array["in_range"] = $this->inRange($year . "-" . $month) ? true : false;
            }
            $outer_array[$month] = $inner_array;
        }
        return $outer_array;
    }
    
    /**
     * 表示する範囲内かどうか調べる
     *
     * @param string $date            
     * @return boolean
     */
    private function inRange($date) {
        return strtotime($this->start_calendar) <= strtotime($date) && strtotime($this->end_calendar) >= strtotime($date) ? true : false;
    }
    
    /**
     * 一月の表示用のカレンダーの配列を返す
     *
     * @param string $year            
     * @param string $month            
     * @return multitype:NULL unknown
     */
    public function getMonthCalendarArray($year, $month) {
        $array = array();
        // 前月
        if ($this->calendar_array[$year][$month][1]["week_day"] != 0) {
            $before_month = $this->getBeforeMonth($year . "-" . $month);
            $before_month_end_day = date("t", strtotime($before_month["year"] . "-" . $before_month["month"]));
            $before_month_start_day = $before_month_end_day - $this->calendar_array[$year][$month][1]["week_day"] + 1;
            foreach(range($before_month_start_day, $before_month_end_day) as $day) {
                $array[] = $this->calendar_array[$before_month["year"]][$before_month["month"]][$day];
            }
        }
        // 今月
        foreach($this->calendar_array[$year][$month] as $key => $value) {
            if (is_integer($key))
                $array[] = $value;
        }
        // 次月
        if ($this->calendar_array[$year][$month]["last_day"]["week_day"] != 6) {
            $next_month = $this->getNextMonth($year . "-" . $month);
            $next_month_end_day = 6 - $this->calendar_array[$year][$month]["last_day"]["week_day"];
            foreach(range(1, $next_month_end_day) as $day) {
                $array[] = $this->calendar_array[$next_month["year"]][$next_month["month"]][$day];
            }
        }
        
        return $array;
    }
    
    /**
     * カレンダーの配列のgetter
     *
     * @access public
     * @return multitype:NULL multitype:multitype:mixed boolean string
     */
    public function getCalendarArray() {
        return $this->calendar_array;
    }
    
    /**
     * 引数は指定された年月 Y-m
     * コンボボックスに表示する年月の配列を返す
     * 前後１０ヶ月
     * Y-m => Y年m月
     *
     * @access public
     * @return multitype:string
     */
    public function getComboBoxArray() {
        $year_month = $this->todays_datetime->format('Y-n');
        $array = array();
        for($i = - 10; $i <= 10; ++ $i) {
            $array[date("Y-n", strtotime($year_month . " " . $i . " month"))] = date("Y年n月", strtotime($year_month . " " . $i . " month"));
        }
        return $array;
    }
    
    /**
     * 最後の日にちを取得
     *
     * @param unknown $date            
     * @return string
     */
    private static function getLastDay($date) {
        $next_month = date("Y-m", strtotime($date . " +1 month"));
        return date("d", strtotime($next_month . " -1 day"));
    }
    
    /**
     * 引数はY-n
     * 1ヶ月先の年と月を連想配列で返す
     * year => , month =>
     *
     * @access public
     * @param string $date            
     * @return multitype:unknown multitype:
     */
    public static function getNextMonth($date) {
        $next_date = date("Y-n", strtotime($date . " +1 month"));
        list($year, $month) = explode("-", $next_date);
        $array = array(
                "year" => $year,
                "month" => $month 
        );
        return $array;
    }
    
    /**
     * 引数はY-n
     * 1ヶ月前の年と月を連想配列で返す
     *
     * @access public
     * @param string $date            
     * @return multitype:unknown multitype:
     */
    public static function getBeforeMonth($date) {
        $before_date = date("Y-n", strtotime($date . " -1 month"));
        list($year, $month) = explode("-", $before_date);
        $array = array(
                "year" => $year,
                "month" => $month 
        );
        return $array;
    }
    
    /**
     * エラーメッセージをセットする
     * 
     * @access private
     * @param string $str            
     */
    private function setErrorMessage($str) {
        if (is_null($this->error_message))
            $this->error_message = array();
        $this->error_message[] = $str;
    }
    /**
     * エラーメッセージを取得する
     * 
     * @access public
     * @return string
     */
    public function getErrorMessage() {
        $str = "";
        if (! is_null($this->error_message)) {
            foreach($this->error_message as $msg) {
                $str = $str . " " . $msg;
            }
        }
        if ($this->schedule_function->isError()) {
            $str = $str . " " . $this->schedule_function->getErrorMessage();
        }
        
        return trim($str);
    }
    /**
     * エラーメッセージがあるかどうか調べる
     *
     * @access public
     * @return boolean
     */
    public function isError() {
        return (is_null($this->error_message) && ! $this->schedule_function->isError()) ? false : true;
    }
}
?>