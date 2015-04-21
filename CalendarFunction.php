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
    // 現在の時間
    private $todays_time;
    // カレンダーの配列
    private $calendar_array;
    // 選択されたカレンダーの年月の配列　年　月　日
    private $selected_date;
    private $start_calendar;
    private $end_calendar;
    // 表示するカレンダーの数
    private $calendar_size;
    
    // 祝日のデータ
    private $public_holiday_array;
    // オークショントピックのデータ
    private $auction_topic_array;
    private $connection;
    
    private $view_id;
    
    public function getViewId(){
        return $this->view_id;
    }
    
    private $modified_flag;
    
    public function getModifiedFlag(){
        return $this->modified_flag;
    }

    /**
     *
     * @param unknown $selected_date
     * @param unknown $calendar_size
     * @param unknown $schedule_start_year
     * @param unknown $schedule_start_month
     * @param unknown $schedule_start_day
     * @param unknown $schedule_start_hour
     * @param unknown $schedule_start_minute
     * @param unknown $schedule_end_year
     * @param unknown $schedule_end_month
     * @param unknown $schedule_end_day
     * @param unknown $schedule_end_hour
     * @param unknown $schedule_end_minute
     * @param unknown $schedule_title
     * @param unknown $schedule_detail
     */
    public function __construct($selected_date, $calendar_size, 
            $schedule_register_flag, $schedule_modify_flag, $schedule_delete_flag, 
            $schedule_start_year, $schedule_start_month, $schedule_start_day, $schedule_start_hour, $schedule_start_minute, 
            $schedule_end_year, $schedule_end_month, $schedule_end_day, $schedule_end_hour, $schedule_end_minute, 
            $schedule_title, $schedule_detail, $view_id) {
        
//         スケジュール編集のフラグをbool値にして検査
        $schedule_register_flag = is_null($schedule_register_flag) ? false : true;
        $schedule_modify_flag = is_null($schedule_modify_flag) ? false : true;
        $schedule_delete_flag = is_null($schedule_delete_flag) ? false : true;
        if (($schedule_register_flag + $schedule_modify_flag + $schedule_delete_flag) > 1){
            $this->setErrorMessage("不正な値です。");
            return;
        } else {
            $this->modified_flag = ($schedule_register_flag + $schedule_modify_flag + $schedule_delete_flag);            
        }

        
        $this->view_id = $view_id;
        $this->today = explode("-", date("Y-n-d"));
        $this->todays_time = explode("-", date("G-i"));
        $this->selected_date = is_null($selected_date) ? $this->today[0] . "-" . $this->today[1] : $selected_date;
        $this->calendar_size = is_null($calendar_size) || ! ctype_digit($calendar_size) ? 3 : $calendar_size;
        $this->connection = new mysqli('localhost', self::DATABASE_USER_NAME, "", self::DATABASE_NAME);
        
        $this->initialize($schedule_register_flag, $schedule_modify_flag, $schedule_delete_flag,
           array(
                $schedule_start_year,
                $schedule_start_month,
                $schedule_start_day
        ), array(
                $schedule_start_hour,
                $schedule_start_minute,
                "00"
        ), array(
                $schedule_end_year,
                $schedule_end_month,
                $schedule_end_day
        ), array(
                $schedule_end_hour,
                $schedule_end_minute,
                "00"
        ), $schedule_title, $schedule_detail);
    
        if (! is_null($view_id)) $this->fetchScheduleById($view_id);
    
        $this->connection->close();
    }
    private $select_all_day_schedule_stmt;
    private $select_end_schedule_stmt;
    private $select_start_schedule_stmt;
    private $select_day_schedule_stmt;
    /**
     * 表示するカレンダーの計算などを行う
     */
    private function initialize($schedule_register_flag, $schedule_modify_flag, $schedule_delete_flag, $schedule_start_date, $schedule_start_time, $schedule_end_date, $schedule_end_time, $schedule_title, $schedule_detail) {
        // day_start, end
        $this->select_all_day_schedule_stmt = mysqli_prepare($this->connection, "SELECT my_schedules_id, title FROM my_schedules where start_time <= ? AND end_time >= ? AND deleted_at is null");
        // day_start, end, end
        $this->select_start_schedule_stmt = mysqli_prepare($this->connection, "SELECT my_schedules_id, title, start_time FROM my_schedules where start_time >= ? AND end_time >= ? AND start_time <= ? AND deleted_at is null");
        // day_start, end, start
        $this->select_end_schedule_stmt = mysqli_prepare($this->connection, "SELECT my_schedules_id, title, end_time FROM my_schedules where start_time <= ? AND end_time <= ? AND end_time >= ? AND deleted_at is null");
        // day_start, end
        $this->select_day_schedule_stmt = mysqli_prepare($this->connection, "SELECT my_schedules_id, title, start_time, end_time FROM my_schedules where start_time >= ? AND end_time <= ? AND deleted_at is null");
    
//         スケジュールの登録
        if ($schedule_register_flag) {
            if (empty($schedule_title) || empty($schedule_start_date) || empty($schedule_start_time) || empty($schedule_end_date) || empty($schedule_end_time)){
                $this->setErrorMessage("情報が入力されていません。");
            } else{
                $start_time = $this->implodeDateAndTimeArray($schedule_start_date, $schedule_start_time);
                $end_time = $this->implodeDateAndTimeArray($schedule_end_date, $schedule_end_time);
                
                $this->insertSchedule($schedule_title, $schedule_detail, $start_time, $end_time);                
            }
        }
        
//         スケジュールの編集
        if ($schedule_modify_flag) {
            if (is_null($this->view_id) || empty($schedule_title) || empty($schedule_start_date) || empty($schedule_start_time) || empty($schedule_end_date) || empty($schedule_end_time)){
                $this->setErrorMessage("情報が入力されていません。");
            } else{
                $start_time = $this->implodeDateAndTimeArray($schedule_start_date, $schedule_start_time);
                $end_time = $this->implodeDateAndTimeArray($schedule_end_date, $schedule_end_time);            
                $this->modifySchedule($this->view_id, $schedule_title, $schedule_detail, $start_time, $end_time);
            }
        }
        
//         スケジュールの削除
        if($schedule_delete_flag) $this->deleteSchedule($this->view_id);
    
        $this->start_calendar = date("Y-n", strtotime($this->selected_date . " -" . floor($this->calendar_size / 2) . " month"));
        list($start_year, $start_month) = explode("-", $this->start_calendar);
        $this->end_calendar = date("Y-n", strtotime($this->selected_date . " +" . ceil($this->calendar_size / 2) . " month -1 month"));
        list($end_year, $end_month) = explode("-", $this->end_calendar);
        $this->calendar_array = $this->createCarendarArray($start_year, $start_month, $end_year, $end_month);
    
        mysqli_stmt_close($this->select_all_day_schedule_stmt);
        mysqli_stmt_close($this->select_start_schedule_stmt);
        mysqli_stmt_close($this->select_end_schedule_stmt);
        mysqli_stmt_close($this->select_day_schedule_stmt);
    }
    
/**
 * 日付の配列と時間の配列をまとめる
 * @param unknown $date_array
 * @param unknown $time_array
 * @return string
 */
    private function implodeDateAndTimeArray($date_array, $time_array){
        $date = implode("-", $date_array);
        $time = implode(":", $time_array);
    
        return $date." ".$time;
    }
    

    /**
     * 今日の年かどうか調べる
     *
     * @param unknown $year            
     * @return boolean
     */
    public function isTodaysYear($year) {
        return $this->today[0] == $year ? true : false;
    }
    
    /**
     * 今日の月かどうか調べる
     *
     * @param unknown $month            
     * @return boolean
     */
    public function isTodaysMonth($month) {
        return $this->today[1] == $month ? true : false;
    }
    
    /**
     * 今日の日かどうか調べる
     *
     * @param unknown $day            
     * @return boolean
     */
    public function isTodaysDay($day) {
        return $this->today[2] == $day ? true : false;
    }
    
    /**
     * 今の時間かどうか調べる
     *
     * @param unknown $hour            
     * @return boolean
     */
    public function isThisHour($hour) {
        return $this->todays_time[0] == $hour ? true : false;
    }
    
    /**
     * 今の分かどうか調べる
     *
     * @param unknown $minute            
     * @return boolean
     */
    public function isThisMinute($minute) {
        return $this->todays_time[1] == $minute ? true : false;
    }
    
    /**
     * データベースに予定を登録する
     *
     * @param unknown $user_title            
     * @param unknown $user_detail            
     * @param unknown $user_start_time            
     * @param unknown $user_end_time            
     */
    private function insertSchedule($title, $detail, $start_time, $end_time) {
        if (strtotime($start_time) >= strtotime($end_time)) {
            $this->setErrorMessage("登録時間がおかしいです。");            
            return;
        }
        
        try {
            $stmt = mysqli_prepare($this->connection, "INSERT INTO my_schedules (title, detail, start_time, end_time, created_at, update_at) values(?, ?, ?, ?, now(), now())");
            mysqli_stmt_bind_param($stmt, 'ssss', $title, $detail, $start_time, $end_time);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } catch ( mysqli_sql_exception $e ) {
            $this->setErrorMessage("SQLエラー：".$e->getMessage());
        }
    }
    
    /**
     * 予定を削除する
     * @param unknown $id
     */
    private function deleteSchedule($id){
        try {
            $stmt = mysqli_prepare($this->connection, "UPDATE my_schedules SET deleted_at = now() where my_schedules_id = ?");
            mysqli_stmt_bind_param($stmt, 's', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } catch ( mysqli_sql_exception $e ){
            $this->setErrorMessage("SQLエラー：".$e->getMessage());
        }
    }
    
    /**
     * 予定を編集する
     * @param unknown $id
     * @param unknown $title
     * @param unknown $detail
     * @param unknown $start_time
     * @param unknown $end_time
     */
    private function modifySchedule($id, $title, $detail, $start_time, $end_time) {
        if (strtotime($start_time) >= strtotime($end_time)) {
            $this->setErrorMessage("登録時間がおかしいです。");
            return;
        }
        try {
            $stmt = mysqli_prepare($this->connection, "UPDATE my_schedules SET title=?, detail=?, start_time=?, end_time=?, update_at=now() where my_schedules_id=?");
            mysqli_stmt_bind_param($stmt, 'sssss', $title, $detail, $start_time, $end_time, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } catch ( mysqli_sql_exception $e){
            $this->setErrorMessage("SQLエラー：".$e->getMessage());
        }
    }
    
    private $view_schedule;
    /**
     * idからスケジュールの情報を読み込む
     * @param unknown $id
     */
    private function fetchScheduleById($id) {
        $this->view_schedule = array();
        $stmt = mysqli_prepare($this->connection, "SELECT title, detail, start_time, end_time from my_schedules where my_schedules_id=? AND deleted_at is NULL");
        mysqli_stmt_bind_param($stmt, 's', $id);
        try {
            if (mysqli_stmt_execute($stmt)) mysqli_stmt_bind_result($stmt, $title, $detail, $start_time, $end_time);
            mysqli_stmt_fetch($stmt);
            $this->view_schedule["title"] = $title;
            $this->view_schedule["detail"] = $detail;
            $start_time_array = $this->explodeDatetime($start_time);
            $end_time_array = $this->explodeDatetime($end_time);
            
            $this->view_schedule["start_year"] = $start_time_array[0];
            $this->view_schedule["start_month"] = $start_time_array[1];
            $this->view_schedule["start_day"] = $start_time_array[2];
            $this->view_schedule["start_hour"] = $start_time_array[3];
            $this->view_schedule["start_minute"] = $start_time_array[4];
            
            $this->view_schedule["end_year"] = $end_time_array[0];
            $this->view_schedule["end_month"] = $end_time_array[1];
            $this->view_schedule["end_day"] = $end_time_array[2];
            $this->view_schedule["end_hour"] = $end_time_array[3];
            $this->view_schedule["end_minute"] = $end_time_array[4];
        } catch ( mysqli_sql_exception $e ) {
            $this->setErrorMessage("スケジュールが取得できませんでした。");
        }
        mysqli_stmt_close($stmt);
    }
    public function isDeleted($id){
        $stmt = mysqli_prepare($this->connection, "SELECT COUNT(*) FROM my_schedules where id=? AND deleted_at is null");
        mysqli_stmt_bind_param($stmt, "s", $id);
        try {
            if (mysqli_stmt_execute($stmt)) mysqli_stmt_bind_result($stmt, $count);
            mysqli_stmt_fetch($stmt);
            return $count == 0 ? true : false;
        } catch ( mysqli_sql_exception $e ){
            $this->setErrorMessage("SQLエラー:".$e->getMessage());
        }
    }
    /**
     * 予定を読み出す状態にあるかどうかを調べる
     * @return boolean
     */
    public function isViewMode() {
        return is_null($this->view_schedule) ? false : true;
    }
    /**
     * idから取得されたスケジュールの情報を返す
     */
    public function getViewScheduleData($key) {
        return $this->view_schedule[$key];
    }
    
    /**
     * datetime型を年、月、日、時、分の配列で返す
     * @param unknown $str
     * @return multitype:unknown Ambigous <>
     */
    private function explodeDatetime($str) {
        $datetime = explode(" ", $str);
        $date_array = explode("-", $datetime[0]);
        $time_array = explode(":", $datetime[1]);
        
        return array(
                $date_array[0],
                $date_array[1],
                $date_array[2],
                $time_array[0],
                $time_array[1] 
        );
    }
    
    /**
     * スケジュールの入った配列を返す
     *
     * @param unknown $year            
     * @param unknown $month            
     * @param unknown $day            
     * @return multitype:string PDOStatement
     */
    private function fetchSchedule($year, $month, $day) {
        try {
            $date = implode("-", array(
                    $year,
                    $month,
                    $day 
            ));
            // 終日
            $day_start_time = $date . " 00:00:00";
            $day_end_time = $date . " 23:59:59";
            
            mysqli_stmt_bind_param($this->select_all_day_schedule_stmt, 'ss', $day_start_time, $day_end_time);
            $schedule = array();
            if (mysqli_stmt_execute($this->select_all_day_schedule_stmt)) {
                mysqli_stmt_bind_result($this->select_all_day_schedule_stmt, $id, $title);
                while (mysqli_stmt_fetch($this->select_all_day_schedule_stmt)) {
                    $schedule[$id] = $title;
                }
            }
            
            // 開始だけかぶるやつ
            mysqli_stmt_bind_param($this->select_start_schedule_stmt, 'sss', $day_start_time, $day_end_time, $day_end_time);
            if (mysqli_stmt_execute($this->select_start_schedule_stmt)) {
                mysqli_stmt_bind_result($this->select_start_schedule_stmt, $id, $title, $start_time);
                while (mysqli_stmt_fetch($this->select_start_schedule_stmt)) {
                    $date = explode(" ", $start_time);
                    $time = explode(":", $date[1]);
                    $schedule[$id] = $time[0] . ":" . $time[1] . "~ " . $title;
                }
            }
            
            // 終了だけかぶるやつ
            mysqli_stmt_bind_param($this->select_end_schedule_stmt, 'sss', $day_start_time, $day_end_time, $day_start_time);
            if (mysqli_stmt_execute($this->select_end_schedule_stmt)) {
                $this->select_end_schedule_stmt->store_result();
                mysqli_stmt_bind_result($this->select_end_schedule_stmt, $id, $title, $end_time);
                while (mysqli_stmt_fetch($this->select_end_schedule_stmt)) {
                    $date = explode(" ", $end_time);
                    $time = explode(":", $date[1]);
                    $schedule[$id] = "~" . $time[0] . ":" . $time[1] . " " . $title;
                }
            }
            
            // 1日で終わってしまう予定
            mysqli_stmt_bind_param($this->select_day_schedule_stmt, 'ss', $day_start_time, $day_end_time);
            if (mysqli_stmt_execute($this->select_day_schedule_stmt)) {
                mysqli_stmt_bind_result($this->select_day_schedule_stmt, $id, $title, $start_time, $end_time);
                while (mysqli_stmt_fetch($this->select_day_schedule_stmt)) {
                    $date = explode(" ", $start_time);
                    $start_time = explode(":", $date[1]);
                    $date = explode(" ", $end_time);
                    $end_time = explode(":", $date[1]);
                    $schedule[$id] = $start_time[0] . ":" . $start_time[1] . "~" . $end_time[0] . ":" . $end_time[1] . " " . $title;
                }
            }
        } catch ( mysqli_sql_exception $e ) {
            $this->setErrorMessage("予定の取得に失敗しました。");
            return array();
        }
        return $schedule;
    }
    
    /**
     * 選択されたカレンダーの年月の配列を返す
     */
    public function getSelectedCalendar() {
        return $this->selected_date;
    }
    
    /**
     * 選択されたカレンダーかどうか確認する
     */
    public function isSelectedCalendar($key) {
        return $this->selected_date == $key ? true : false;
    }
    
    /**
     * 表示するカレンダーの数を返す
     */
    public function getCalendarSize() {
        return $this->calendar_size;
    }
    
    /**
     * 今日かどうかを調べる
     */
    public function isToday($year, $month, $day) {
        return strtotime(implode("-", array(
                $year,
                $month,
                $day 
        ))) === strtotime(implode("-", $this->today)) ? true : false;
    }
    
    /**
     * 祝日かどうかを調べる
     */
    public static function isHoliday($day) {
        return empty($day["holiday_name"]) ? false : true;
    }
    
    /**
     * 祝日名を返す
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
     */
    private function getPublicHolidayData($start_year, $start_month, $end_year, $end_month) {
        $year_start_option = self::YEAR_START_OPTION_NAME . "=" . $start_year;
        $year_end_option = self::YEAR_END_OPTION_NAME . "=" . $end_year;
        $month_start_option = self::MONTH_START_OPTION_NAME . "=" . $start_month;
        $month_end_option = self::MONTH_END_OPTION_NAME . "=" . $end_month;
        $url = self::SERVER . "?" . $year_start_option . "&" . $month_start_option . "&" . $year_end_option . "&" . $month_end_option . "&" . self::FIXED_OPTION;
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
     * @param unknown $start_date            
     * @return Ambigous <multitype:, multitype:string >
     */
    private function getAucfanTopicData($start_date) {
        $topics_array = array();
        foreach(range(1, 1) as $page) {
            $topic = array();
            $url = self::AUCTOPIC . "?paged=" . $page;
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
     */
    private function createCarendarArray($start_year, $start_month, $end_year, $end_month) {
        $array = array();
        // 指定された最初の月と最後の月の範囲を広げる
        $start = date("Y-n", strtotime($start_year . "-" . $start_month . " - 1 month"));
        list($start_year, $start_month) = explode("-", $start);
        $end = date("Y-n", strtotime($end_year . "-" . $end_month . " + 1 month"));
        list($end_year, $end_month) = explode("-", $end);
        
        $this->public_holiday_array = $this->getPublicHolidayData($start_year, $start_month, $end_year, $end_month);
        $this->auction_topic_array = $this->getAucfanTopicData($this->start_calendar);
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
     */
    private function createYearCarendarArray($year, $start_month, $end_month) {
        $outer_array = array();
        for($month = $start_month; $month <= $end_month; ++ $month) {
            $inner_array = array();
            $day_array = array();
            for($day = 1; $day <= date("t", strtotime($year . "-" . $month)); ++ $day) {
                $array['schedules'] = $this->fetchSchedule($year, $month, $day);
                $array['week_day'] = (string) date("w", strtotime($year . "-" . $month . "-" . $day));
                $array['day'] = $day;
                if ($day == $this->today[2] && $month == $this->today[1] && $year == $this->today[0]) {
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
     * @param unknown $date            
     * @return boolean
     */
    private function inRange($date) {
        return strtotime($this->start_calendar) <= strtotime($date) && strtotime($this->end_calendar) >= strtotime($date) ? true : false;
    }
    
    /**
     * 一月の表示用のカレンダーの配列を返す
     *
     * @param unknown $year            
     * @param unknown $month            
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
     * @return multitype:string
     */
    public function getComboBoxArray() {
        $year_month = $this->today[0] . "-" . $this->today[1];
        $array = array();
        for($i = - 10; $i <= 10; ++ $i) {
            $array[date("Y-n", strtotime($year_month . " " . $i . " month"))] = date("Y年n月", strtotime($year_month . " " . $i . " month"));
        }
        return $array;
    }
    public function getScheduleYear() {
        $array = array();
        foreach(range(0, 3) as $year)
            $array[] = $this->today[0] + $year;
        
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
     * @param unknown $date            
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
    
    private $error_message;
    /**
     * エラーメッセージをセットする
     * @param unknown $str
     */
    private function setErrorMessage($str){
        if (is_null($this->error_message)) $this->error_message = array();
        $this->error_message[] = $str;
    }
    /**
     * エラーメッセージを取得する
     * @return string
     */
    public function getErrorMessage(){
        $str = "";
        foreach ($this->error_message as $msg) $str = $str." ".$msg; 
        return trim($str);
    }
    /**
     * エラーメッセージがあるかどうか調べる
     * @return boolean
     */
    public function isError(){
        return is_null($this->error_message) ? false : true;
    }
}
?>