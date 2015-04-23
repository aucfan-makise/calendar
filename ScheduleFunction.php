<?php
require_once "Properties.php";
require_once "CalendarFunction.php";

class ScheduleFunction
{

    private static $error_message;

    private $connection;

    private $schedule;

    private $schedules_array = array();

    private $modify_mode = array(
        "register" => false,
        "modify" => false,
        "delete" => false
    );

    public function __construct($post_data)
    {
        $this->initialize($post_data);
    }

    public function __destruct()
    {
        $this->connection->close();
    }

    /**
     *
     * @access private
     * @param array $post_data            
     */
    private function initialize($post_data)
    {
        if (! is_null($post_data)) {
            $this->dataCheck($post_data);
            $this->connection = new mysqli('localhost', Properties::DATABASE_USER_NAME, "", Properties::DATABASE_NAME);
            
            // スケジュールの登録
            if ($this->modify_mode["register"]) {
                $this->insertSchedule();
            } elseif ($this->modify_mode["delete"]) {
                $this->deleteSchedule();
            } elseif ($this->modify_mode["modify"]) {
                $this->modifySchedule();
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
    public function fetchSchedule($calendar_start_datetime, $calendar_end_datetime)
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
            
            mysqli_stmt_close($stmt);
        } catch (mysqli_sql_exception $e) {
            $this->setErrorMessage("SQLエラー；" . $e->getMessage());
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
     * idからスケジュールを読み出す
     *
     * @param string $id            
     * @return multitype:unknown multitype:
     */
    public function fetchScheduleById($id)
    {
        $schedule = array();
        $stmt = mysqli_prepare($this->connection, "SELECT title, detail, start_time, end_time from my_schedules where my_schedules_id=? AND deleted_at is NULL");
        mysqli_stmt_bind_param($stmt, 's', $id);
        try {
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_bind_result($stmt, $title, $detail, $start_time, $end_time);
            } else {
                throw new Exception();
            }
            if (is_null(mysqli_stmt_fetch($stmt)))
                throw new Exception("そんなスケジュールはありません。");
            $schedule["title"] = $title;
            $schedule["detail"] = $detail;
            $schedule["start_time"] = date_parse($start_time);
            $schedule["end_time"] = date_parse($end_time);
        } catch (mysqli_sql_exception $e) {
            $this->setErrorMessage("SQLのエラー");
        } catch (Exception $e) {
            $this->setErrorMessage("スケジュールが取得できませんでした。" . $e->getMessage());
        }
        mysqli_stmt_close($stmt);
        return $schedule;
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
     * 受け取った値のチェックをする
     *
     * @param array $post_data            
     * @throws Exception
     */
    private function dataCheck($post_data)
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
     * 日付時間のチェックをする
     *
     * @access private
     * @param DateTime $datetime            
     * @throws Exception
     */
    private function dateTimeCheck($datetime)
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