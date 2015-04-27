<?php
require_once "Properties.php";
require_once "CalendarFunction.php";

class ScheduleFunction
{

    private static $error_message;

    private $connection;

    private $schedules_array = array();
    
    // スケジュールの登録編集関連
    private $schedule;

    private $modify_mode = array(
        "register" => false,
        "modify" => false,
        "delete" => false
    );
    
    // api関連
    private $api_start_datetime;

    private $api_end_datetime;
    
    private $api_id;

    /**
     *
     * @param array $get_data            
     * @param array $post_data            
     */
    public function __construct(array $get_data, array $post_data = null)
    {
        if (! is_null($post_data)) {
            $this->scheduleEditInitialize($post_data);
        } elseif (! is_null($get_data)) {
            $this->apiInitialize($get_data);
        }
    }

    /**
     * データベースに接続する
     */
    private function databaseConnect()
    {
        $this->connection = new mysqli('localhost', Properties::DATABASE_USER_NAME, "", Properties::DATABASE_NAME);
    }

    /**
     * データベースを閉じる
     */
    public function __destruct()
    {
        $this->connection->close();
    }

    /**
     * api関連のもの
     * 
     * @access private
     * @param array $get_data            
     */
    private function apiInitialize(array $get_data)
    {
        $this->getDataCheck($get_data);
        $this->databaseConnect();
        
        if (isset($this->api_id)){
            $this->getScheduleById();
        } else {
            $this->getSchedule($this->api_start_datetime, $this->api_end_datetime);
        }
    }

    /**
     * $_GETのチェック
     * 
     * @access private
     * @param array $get_data            
     * @throws Exception
     */
    private function getDataCheck(array $get_data)
    {
        try {
            if (isset($get_data["id"]) && ctype_digit($get_data["id"])){
                $this->api_id = $get_data["id"];
            } else {
                $this->api_start_datetime = new DateTime($get_data["schedule_start"]);
                $this->api_end_datetime = new DateTime($get_data["schedule_end"]);
                
                if ($this->api_start_datetime > $this->api_end_datetime){
                    throw new Exception();
                }
            }
        } catch (Exception $e) {
            $this->setErrorMessage("パラメータが不正です。日付が正しくありません" . $e->getMessage());
        }
    }

    /**
     * スケジュールの編集に関するもの
     * 
     * @access private
     * @param array $post_data            
     */
    private function scheduleEditInitialize(array $post_data)
    {
        $this->postDataCheck($post_data);
        $this->databaseConnect();
        
        // スケジュールの登録
        if ($this->modify_mode["register"]) {
            $this->insertSchedule();
        } elseif ($this->modify_mode["delete"]) {
            $this->deleteSchedule();
        } elseif ($this->modify_mode["modify"]) {
            $this->modifySchedule();
        }
    }

    /**
     * 受け取った値のチェックをする
     *
     * @param array $post_data            
     * @throws Exception
     */
    private function postDataCheck(array $post_data)
    {
        foreach ($post_data as $data) {
            try {
                $this->schedule["detail"] = $post_data["schedule_detail"];
                
                // 値があるべきものはあるかどうかをチェック
                foreach ($post_data as $key => $value) {
                    if (empty($value) && $key != "schedule_detail")
                        throw new Exception($key . "の値がありません。");
                }
                $this->schedule["title"] = $post_data["schedule_title"];
                if (isset($post_data["register"]))
                    $this->modify_mode["register"] = true;
                if (isset($post_data["modify"]))
                    $this->modify_mode["modify"] = true;
                if (isset($post_data["delete"]))
                    $this->modify_mode["delete"] = true;
                
                if (isset($post_data["view_id"]))
                    $this->schedule["view_id"] = $post_data["view_id"];
                    
                    // モードのチェック
                if (! (($this->modify_mode["register"] xor $this->modify_mode["modify"]) xor $this->modify_mode["delete"])) {
                    throw new Exception("モードが変です。");
                }
                
                $start_datetime = new DateTime();
                $date_result = $start_datetime->setDate($post_data["schedule_start_year"], $post_data["schedule_start_month"], $post_data["schedule_start_day"]);
                $time_result = $start_datetime->setTime($post_data["schedule_start_hour"], $post_data["schedule_start_minute"]);
                if ($date_result === false || $time_result === false)
                    throw new Exception("開始日時が不正な値です。");
                
                $this->dateTimeCheck($start_datetime);
                $this->schedule["start"] = $start_datetime;
                
                $end_datetime = new DateTime();
                $date_result = $end_datetime->setDate($post_data["schedule_end_year"], $post_data["schedule_end_month"], $post_data["schedule_end_day"]);
                $time_result = $end_datetime->setTime($post_data["schedule_end_hour"], $post_data["schedule_end_minute"]);
                if ($date_result === false || $time_result === false)
                    throw new Exception("終了日時が不正な値です。");
                
                $this->dateTimeCheck($end_datetime);
                $this->schedule["end"] = $end_datetime;
                // 開始時間と終了時間の関係をチェック
                if ($start_datetime >= $end_datetime)
                    throw new Exception("登録時間がおかしいです。");
            } catch (Exception $e) {
                $this->setErrorMessage("不正なパラメータです。" . $e->getMessage());
                foreach ($this->modify_mode as $key => $value) {
                    $this->modify_mode[$key] = false;
                }
                return;
            }
        }
    }

    /**
     * カレンダーの最初と最後の日付から予定を取得する
     *
     * @access public
     * @param DateTime $calendar_start_datetime            
     * @param DateTime $calendar_end_datetime            
     */
    public function getSchedule(DateTime $calendar_start_datetime, DateTime $calendar_end_datetime)
    {
        $calendar_start_datetime = new DateTime($calendar_start_datetime->format('Y-n-') . "1");
        $calendar_start_datetime->setTime(0, 0, 0);
        $calendar_end_datetime = new DateTime($calendar_end_datetime->format('Y-n-' . date("t", strtotime($calendar_end_datetime->format('Y-n')))));
        $calendar_end_datetime->setTime(23, 59, 59);
        
        $start_datetime = $calendar_start_datetime->format('Y-m-d H:i:s');
        $end_datetime = $calendar_end_datetime->format('Y-m-d H:i:s');
        
        try {
            $stmt = mysqli_prepare($this->connection, "SELECT my_schedules_id, title, detail, start_time, end_time from my_schedules where ((start_time > ? and start_time < ?) or (end_time > ? and end_time < ?)) AND deleted_at is null");
            mysqli_stmt_bind_param($stmt, 'ssss', $start_datetime, $end_datetime, $start_datetime, $end_datetime);
            $this->fetchSchedule($stmt);
        } catch (mysqli_sql_exception $e) {
            $this->setErrorMessage("SQLエラー；" . $e->getMessage());
        }
        mysqli_stmt_close($stmt);
    }

    /**
     * idからスケジュールを読み出す
     *
     * @access public
     * @param string $id
     * @return multitype:unknown multitype:
     */
    public function getScheduleById()
    {
        try {
            $stmt = mysqli_prepare($this->connection, "SELECT my_schedules_id, title, detail, start_time, end_time from my_schedules where my_schedules_id=? AND deleted_at is NULL");
            mysqli_stmt_bind_param($stmt, 's', $this->api_id);
            $this->fetchSchedule($stmt);
        } catch (mysqli_sql_exception $e) {
            $this->setErrorMessage("SQLのエラー");
        } catch (Exception $e) {
            $this->setErrorMessage("スケジュールが取得できませんでした。" . $e->getMessage());
        }
        mysqli_stmt_close($stmt);
    }
    
    /**
     * SQLに問い合わせて得た情報を配列に格納する
     * @access private
     * @param mysqli_stmt $stmt
     */
    private function fetchSchedule($stmt){
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_bind_result($stmt, $id, $title, $detail, $start_time, $end_time);
            while (mysqli_stmt_fetch($stmt)) {
                $schedule_start_datetime = new DateTime($start_time);
                $schedule_end_datetime = new DateTime($end_time);
                for ($current = new DateTime($schedule_start_datetime->format("Y-m-d")); $current <= $schedule_end_datetime; $current = $current->add(new DateInterval('P1D'))) {
                    $schedule_array = array(
                        "title" => $title,
                        "detail" => $detail
                    );
                    $formated_current = $current->format('Y-m-d');
                    $formated_schedule_start_datetime = $schedule_start_datetime->format('Y-m-d');
                    $formated_schedule_end_datetime = $schedule_end_datetime->format('Y-m-d');
        
                    if ($formated_current === $formated_schedule_start_datetime && $formated_current === $formated_schedule_end_datetime) {
                        $schedule_array["start_time"] = $schedule_start_datetime->format('G:i');
                        $schedule_array["end_time"] = $schedule_end_datetime->format('G:i');
                        $this->schedules_array = $this->createDateArray(date_parse($current->format('Y-n-j')), $this->schedules_array, $id, $schedule_array);
                        continue;
                    }
                    if ($formated_current === $formated_schedule_start_datetime) {
                        $schedule_array["start_time"] = $schedule_start_datetime->format('G:i');
                        $schedule_array["end_time"] = "23:59";
                        $this->schedules_array = $this->createDateArray(date_parse($current->format('Y-n-j')), $this->schedules_array, $id, $schedule_array);
                        continue;
                    }
                    if ($formated_current === $formated_schedule_end_datetime) {
                        $schedule_array["start_time"] = "00:00";
                        $schedule_array["end_time"] = $schedule_end_datetime->format('G:i');
                        $this->schedules_array = $this->createDateArray(date_parse($current->format('Y-n-j')), $this->schedules_array, $id, $schedule_array);
                        continue;
                    }
        
                    $schedule_array["start_time"] = "00:00";
                    $schedule_array["end_time"] = "23:59";
                    $this->schedules_array = $this->createDateArray(date_parse($current->format('Y-n-j')), $this->schedules_array, $id, $schedule_array);
                }
            }
        }
        
    }

    /**
     * カレンダーの予定の入った配列を返す
     *
     * @access public
     * @return Ambigous <multitype:, multitype:>
     */
    public function getSchedulesArray()
    {
        return $this->schedules_array;
    }

    /**
     * 年月日の配列と追加する多次元配列を渡すとくわえてから返す
     *
     * @access private
     * @param array $date            
     * @param array $date_array            
     * @param string $input_key            
     * @param array $input_array            
     * @return multitype:
     */
    private function createDateArray($date, $date_array, $input_key, $input_array)
    {
        if (! in_array($date["year"], $date_array))
            $date_array[$date["year"]][] = array();
        if (! in_array($date["month"], $date_array[$date["year"]]))
            $date_array[$date["year"]][$date["month"]][] = array();
        if (! in_array($date["day"], $date_array[$date["year"]][$date["month"]]))
            $date_array[$date["year"]][$date["month"]][$date["day"]][$input_key] = $input_array;
        
        return $date_array;
    }


    /**
     * 予定を登録する
     *
     * @access private
     */
    private function insertSchedule()
    {
        try {
            $stmt = mysqli_prepare($this->connection, "INSERT INTO my_schedules (title, detail, start_time, end_time, created_at, update_at) values(?, ?, ?, ?, now(), now())");
            mysqli_stmt_bind_param($stmt, 'ssss', $this->schedule["title"], $this->schedule["detail"], $this->schedule["start"]->format('Y-n-j G:i'), $this->schedule["end"]->format('Y-n-j G:i'));
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } catch (mysqli_sql_exception $e) {
            $this->setErrorMessage("SQLエラー：" . $e->getMessage());
        }
    }

    /**
     * 予定を削除する
     *
     * @access privae
     */
    private function deleteSchedule()
    {
        try {
            $stmt = mysqli_prepare($this->connection, "UPDATE my_schedules SET deleted_at = now() where my_schedules_id = ?");
            mysqli_stmt_bind_param($stmt, 's', $this->schedule["view_id"]);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } catch (mysqli_sql_exception $e) {
            $this->setErrorMessage("SQLエラー：" . $e->getMessage());
        }
    }

    /**
     * 予定を編集する
     *
     * @access private
     */
    private function modifySchedule()
    {
        try {
            $stmt = mysqli_prepare($this->connection, "UPDATE my_schedules SET title=?, detail=?, start_time=?, end_time=?, update_at=now() where my_schedules_id=?");
            mysqli_stmt_bind_param($stmt, 'sssss', $this->schedule["title"], $this->schedule["detail"], $this->schedule["start"]->format('Y-n-j G:i'), $this->schedule["end"]->format('Y-n-j G:i'), $this->schedule["view_id"]);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } catch (mysqli_sql_exception $e) {
            $this->setErrorMessage("SQLエラー：" . $e->getMessage());
        }
    }

    /**
     * 日付時間のチェックをする
     *
     * @access private
     * @param DateTime $datetime            
     * @throws Exception
     */
    private function dateTimeCheck(DateTime $datetime)
    {
        $datetime = date_parse($datetime->format("Y-m-d"));
        if ($datetime["year"] < 2015 || 2018 < $datetime["year"])
            throw new Exception("年が不正です。");
    }

    /**
     * エラーメッセージを格納する
     *
     * @access private
     * @param string $str            
     */
    private function setErrorMessage($str)
    {
        if (is_null($this->error_message))
            $this->error_message = array();
        $this->error_message[] = $str;
    }

    /**
     * エラーメッセージを出力する
     *
     * @access public
     * @return string
     */
    public function getErrorMessage()
    {
        $str = "";
        foreach ($this->error_message as $msg) {
            $str = $str . " " . $msg;
        }
        return $str;
    }

    /**
     * エラーメッセージがあるかどうかを確認する
     *
     * @access public
     * @return boolean
     */
    public function isError()
    {
        return is_null($this->error_message) ? false : true;
    }
}
?>