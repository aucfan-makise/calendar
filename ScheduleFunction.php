<?php
require_once 'Properties.php';
require_once 'CalendarFunction.php';
require_once 'AbstractFunction.php';
require_once 'AccountFunction.php';

class ScheduleFunction extends AbstractFunction
{

    private $connection;

    private $user_id;

    private $schedules_array = array();
    
    // スケジュールの登録編集関連
    private $schedule;

    private $modify_mode = array(
        'register' => false,
        'modify' => false,
        'delete' => false
    );
    
    // api関連
    private $api_start_datetime;

    private $api_end_datetime;

    private $api_id;
    
    // xml json
    private $api_format;

    public function getApiFormat()
    {
        return $this->api_format;
    }

    /**
     *
     * @param array $get_data            
     * @param array $post_data            
     */
    public function __construct(array $session_data = null, array $get_data, array $post_data = null)
    {
        $this->databaseConnect();
        // postがあればカレンダー
        // なくてgetがあればapi
        if (! is_null($post_data)) {
            $this->checkSessionData($session_data);
            $this->scheduleEditInitialize($get_data, $post_data);
        } elseif (! is_null($get_data)) {
            $this->apiInitialize($get_data);
            session_start();
            $this->checkSessionData($_SESSION);
        }
    }

    /**
     * データベースに接続する
     */
    private function databaseConnect()
    {
        $this->connection = new mysqli('localhost', Properties::DATABASE_USER_NAME, '', Properties::DATABASE_NAME);
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
        try {
            $this->checkGetData($get_data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     *
     * @see AbstractFunction::checkSessionData
     * @param array $session_data            
     */
    protected function checkSessionData(array $session_data)
    {
        $this->user_id = $session_data['user_id'];
    }

    /**
     *
     * @see AbstractFunction::checkGetData
     * @param array $get_data            
     * @throws Exception
     */
    protected function checkGetData(array $get_data)
    {
        try {
            if (isset($get_data['address']) && isset($get_data['password'])) {
                $account_function = new AccountFunction(null, $get_data);
            } else {
                throw new Exception('ユーザ名かパスワードが入力されていません。');
            }
            if (isset($get_data['id'])) {
                if (ctype_digit($get_data['id'])) {
                    $this->api_id = $get_data['id'];
                } else {
                    throw new Exception('idが間違っています。');
                }
            } elseif (isset($get_data['schedule_start']) && isset($get_data['schedule_end'])) {
                $this->api_start_datetime = new DateTime($get_data['schedule_start']);
                $this->api_end_datetime = new DateTime($get_data['schedule_end']);
                
                if ($this->api_start_datetime > $this->api_end_datetime) {
                    throw new Exception('指定した期間が正しくありません。');
                }
            }
            
            if (isset($get_data['format'])) {
                $this->api_format = $get_data['format'];
                if ($this->api_format !== 'xml' && $this->api_format !== 'json')
                    throw new Exception('サポートしていないフォーマットです。');
            } else {
                throw new Exception('フォーマットが指定されていません。');
            }
        } catch (Exception $e) {
            throw new Exception('パラメータが不正です。' . $e->getMessage());
        }
    }

    /**
     * スケジュールの編集に関するもの
     *
     * @access private
     * @param array $post_data            
     */
    private function scheduleEditInitialize(array $get_data, array $post_data)
    {
        $this->checkPostData($post_data);
        
        // スケジュールの編集
        // ログインしていないユーザには編集をさせない
        if (! is_null($this->user_id)) {
            if ($this->modify_mode['register']) {
                $this->insertSchedule();
            } elseif ($this->modify_mode['delete']) {
                $this->deleteSchedule();
            } elseif ($this->modify_mode['modify']) {
                $this->modifySchedule();
            }
        }
        
        $this->schedule['view_id'] = $get_data['view_id'];
    }

    /**
     *
     * @see AbstractFunction::checkPostData
     * @param array $post_data            
     * @throws Exception
     */
    protected function checkPostData(array $post_data)
    {
        foreach ($post_data as $data) {
            try {
                $this->schedule['detail'] = $post_data['schedule_detail'];
                
                // 値があるべきものはあるかどうかをチェック
                foreach ($post_data as $key => $value) {
                    if (empty($value) && $key != 'schedule_detail')
                        throw new Exception($key . 'の値がありません。');
                }
                $this->schedule['title'] = $post_data['schedule_title'];
                if (isset($post_data['register']))
                    $this->modify_mode['register'] = true;
                if (isset($post_data['modify']))
                    $this->modify_mode['modify'] = true;
                if (isset($post_data['delete']))
                    $this->modify_mode['delete'] = true;
                
                if (isset($post_data['view_id']))
                    $this->schedule['view_id'] = $post_data['view_id'];
                    
                    // モードのチェック
                if (! (($this->modify_mode['register'] 
                xor $this->modify_mode['modify']) 
                xor $this->modify_mode['delete'])) {
                    throw new Exception('モードが変です。');
                }
                
                $start_datetime = new DateTime();
                $date_result = $start_datetime->setDate(
                    $post_data['schedule_start_year'], 
                    $post_data['schedule_start_month'], 
                    $post_data['schedule_start_day']);
                $time_result = $start_datetime->setTime(
                    $post_data['schedule_start_hour'], 
                    $post_data['schedule_start_minute']);
                if ($date_result === false || $time_result === false)
                    throw new Exception('開始日時が不正な値です。');
                
                $this->dateTimeCheck($start_datetime);
                $this->schedule['start'] = $start_datetime;
                
                $end_datetime = new DateTime();
                $date_result = $end_datetime->setDate(
                    $post_data['schedule_end_year'], 
                    $post_data['schedule_end_month'], 
                    $post_data['schedule_end_day']);
                $time_result = $end_datetime->setTime(
                    $post_data['schedule_end_hour'], 
                    $post_data['schedule_end_minute']);
                if ($date_result === false || $time_result === false)
                    throw new Exception('終了日時が不正な値です。');
                
                $this->dateTimeCheck($end_datetime);
                $this->schedule['end'] = $end_datetime;
                // 開始時間と終了時間の関係をチェック
                if ($start_datetime >= $end_datetime)
                    throw new Exception('登録時間がおかしいです。');
            } catch (Exception $e) {
                $this->setErrorMessage('不正なパラメータです。' . $e->getMessage());
                foreach ($this->modify_mode as $key => $value) {
                    $this->modify_mode[$key] = false;
                }
                return;
            }
        }
    }

    /**
     * api用のデータを取得する
     *
     * @access public
     * @throws Exception
     * @return Ambigous <boolean, multitype:multitype:unknown >
     */
    public function getApiSchedule()
    {
        try {
            $return;
            if (! is_null($this->api_start_datetime)) {
                $return = $this->getSchedule($this->api_start_datetime, $this->api_end_datetime);
            } else {
                $return = $this->getApiScheduleById();
            }
            // とれたデータが０個だったらエラー
            if (count($return) == 0)
                throw new Exception('スケジュールがありません。');
            
            return $return;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
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
        $calendar_start_datetime = new DateTime($calendar_start_datetime->format('Y-n-') . '1');
        $calendar_start_datetime->setTime(0, 0, 0);
        $calendar_end_datetime = new DateTime(
            $calendar_end_datetime->format(
                'Y-n-' . date('t', strtotime($calendar_end_datetime->format('Y-n'))))
            );
        $calendar_end_datetime->setTime(23, 59, 59);
        
        $start_datetime = $calendar_start_datetime->format('Y-m-d H:i:s');
        $end_datetime = $calendar_end_datetime->format('Y-m-d H:i:s');
        
        try {
            $stmt = mysqli_prepare($this->connection, "SELECT schedules.schedules_id, schedules.title, schedules.detail, schedules.start_time, schedules.end_time FROM schedules inner join user_schedule_relations on schedules.schedules_id = user_schedule_relations.schedules_id where user_schedule_relations.user_id = ? AND ((schedules.start_time > ? and schedules.start_time < ?) or (schedules.end_time > ? and schedules.end_time < ?)) AND schedules.deleted_at is null");
            if ($stmt === false)
                throw new mysqli_sql_exception('prepare');
            mysqli_stmt_bind_param($stmt, 'sssss', 
                $this->user_id, 
                $start_datetime, 
                $end_datetime, 
                $start_datetime, 
                $end_datetime);
            // apiならapi用の配列に入れて返す
            if (! is_null($this->api_start_datetime)) {
                return $this->fetchApiScheduleArray($stmt);
            }
            // カレンダーならカレンダー用
            $this->fetchSchedule($stmt);
            $close_result = mysqli_stmt_close($stmt);
            if ($close_result === 'false')
                throw new mysqli_sql_exception('close');
        } catch (mysqli_sql_exception $e) {
            $this->setErrorMessage('SQLエラー；' . $e->getMessage());
        }
    }

    /**
     * idからスケジュールを読み出す
     *
     * @access public
     * @param string $id            
     */
    public function getApiScheduleById()
    {
        $array;
        try {
            $stmt = mysqli_prepare($this->connection, "SELECT schedules.schedules_id, schedules.title, schedules.detail, schedules.start_time, schedules.end_time from schedules inner join user_schedule_relations on schedules.schedules_id = user_schedule_relations.schedules_id where user_schedule_relations.user_id=? AND schedules.schedules_id=? AND schedules.deleted_at is NULL");
            if ($stmt === false)
                throw new mysqli_sql_exception('prepare');
            $bind_result = mysqli_stmt_bind_param($stmt, 'ss', $this->user_id, $this->api_id);
            if ($bind_result === false)
                throw new mysqli_sql_exception('bind');
            $array = $this->fetchApiScheduleArray($stmt);
            $close_result = mysqli_stmt_close($stmt);
            if ($close_result === false)
                throw new mysqli_sql_exception('close');
        } catch (mysqli_sql_exception $e) {
            $this->setErrorMessage('SQLエラー:' . $e->getMessage() . '-' . mysqli_error($this->connection));
        } catch (Exception $e) {
            $this->setErrorMessage('スケジュールが取得できませんでした。' . $e->getMessage());
        }
        return $array;
    }

    /**
     * idを用いてapi用のデータを取得する
     *
     * @access public
     * @throws Exception
     * @return multitype:unknown multitype:
     */
    public function getScheduleById()
    {
        $schedule = array();
        try {
            $stmt = mysqli_prepare($this->connection, "SELECT schedules.title, schedules.detail, schedules.start_time, schedules.end_time from schedules inner join user_schedule_relations on schedules.schedules_id = user_schedule_relations.schedules_id where user_schedule_relations.user_id=? AND schedules.schedules_id=? AND schedules.deleted_at is NULL");
            if ($stmt === false)
                throw new mysqli_sql_exception('prepare');
            $bind_result = mysqli_stmt_bind_param($stmt, 'ss', $this->user_id, $this->schedule['view_id']);
            if ($bind_result === false)
                throw new mysqli_sql_exception('bind');
            if (mysqli_stmt_execute($stmt)) {
                $bind_result = mysqli_stmt_bind_result($stmt, $title, $detail, $start_time, $end_time);
                if ($bind_result === false)
                    throw new mysqli_sql_exception('bind');
            } else {
                throw new mysqli_sql_exception('execute');
            }
            if (is_null(mysqli_stmt_fetch($stmt)))
                throw new Exception('そんなスケジュールはありません。');
            $schedule['title'] = $title;
            $schedule['detail'] = $detail;
            $schedule['start_time'] = date_parse($start_time);
            $schedule['end_time'] = date_parse($end_time);
        } catch (mysqli_sql_exception $e) {
            $this->setErrorMessage('SQLエラー:' . $e->getMessage() . '-' . mysqli_error($this->connection));
        } catch (Exception $e) {
            $this->setErrorMessage('スケジュールが取得できませんでした。' . $e->getMessage());
        }
        mysqli_stmt_close($stmt);
        return $schedule;
    }

    /**
     * api用のスケジュールの配列を作る
     *
     * @param mysqli_stmt $stmt            
     * @return boolean|multitype:multitype:unknown
     */
    private function fetchApiScheduleArray($stmt)
    {
        if (! mysqli_stmt_execute($stmt))
            return false;
        
        mysqli_stmt_bind_result($stmt, $id, $title, $detail, $start_time, $end_time);
        $items = array();
        while (mysqli_stmt_fetch($stmt)) {
            $item = array(
                'id' => $id,
                'title' => $title,
                'detail' => $detail,
                'start_time' => $start_time,
                'end_time' => $end_time
            );
            $items[] = $item;
        }
        return $items;
    }

    /**
     * SQLに問い合わせて得た情報を配列に格納する
     *
     * @access private
     * @param mysqli_stmt $stmt            
     */
    private function fetchSchedule($stmt)
    {
        if (! mysqli_stmt_execute($stmt))
            return false;
        
        mysqli_stmt_bind_result($stmt, $id, $title, $detail, $start_time, $end_time);
        while (mysqli_stmt_fetch($stmt)) {
            $schedule_start_datetime = new DateTime($start_time);
            $schedule_end_datetime = new DateTime($end_time);
            
            // 取得開始年月から取得終了年月の最終日まで一日おきに回るループ
            $current = new DateTime($schedule_start_datetime->format('Y-m-d'));
            for ($interval = new DateInterval('P1D'); $current <= $schedule_end_datetime; $current = $current->add($interval)) {
                $schedule_array = array(
                    'title' => $title,
                    'detail' => $detail
                );
                $formated_current = $current->format('Y-m-d');
                $formated_schedule_start_datetime = $schedule_start_datetime->format('Y-m-d');
                $formated_schedule_end_datetime = $schedule_end_datetime->format('Y-m-d');
                
                if ($formated_current === $formated_schedule_start_datetime && $formated_current === $formated_schedule_end_datetime) {
                    $schedule_array['start_time'] = $schedule_start_datetime->format('G:i');
                    $schedule_array['end_time'] = $schedule_end_datetime->format('G:i');
                    $this->schedules_array = $this->createDateArray(date_parse($current->format('Y-n-j')), $this->schedules_array, $id, $schedule_array);
                    continue;
                }
                if ($formated_current === $formated_schedule_start_datetime) {
                    $schedule_array['start_time'] = $schedule_start_datetime->format('G:i');
                    $schedule_array['end_time'] = '23:59';
                    $this->schedules_array = $this->createDateArray(date_parse($current->format('Y-n-j')), $this->schedules_array, $id, $schedule_array);
                    continue;
                }
                if ($formated_current === $formated_schedule_end_datetime) {
                    $schedule_array['start_time'] = '00:00';
                    $schedule_array['end_time'] = $schedule_end_datetime->format('G:i');
                    $this->schedules_array = $this->createDateArray(date_parse($current->format('Y-n-j')), $this->schedules_array, $id, $schedule_array);
                    continue;
                }
                
                $schedule_array['start_time'] = '00:00';
                $schedule_array['end_time'] = '23:59';
                $this->schedules_array = $this->createDateArray(date_parse($current->format('Y-n-j')), $this->schedules_array, $id, $schedule_array);
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
        if (! in_array($date['year'], $date_array))
            $date_array[$date['year']][] = array();
        if (! in_array($date['month'], $date_array[$date['year']]))
            $date_array[$date['year']][$date['month']][] = array();
        if (! in_array($date['day'], $date_array[$date['year']][$date['month']]))
            $date_array[$date['year']][$date['month']][$date['day']][$input_key] = $input_array;
        
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
            // スケジュールのインサート
            $stmt = mysqli_prepare($this->connection, "INSERT INTO schedules (title, detail, start_time, end_time, created_at, update_at) values(?, ?, ?, ?, now(), now())");
            if ($stmt === false)
                throw new mysqli_sql_exception('prepare');
            $bind_result = mysqli_stmt_bind_param($stmt, 'ssss', $this->schedule["title"], $this->schedule["detail"], $this->schedule["start"]->format('Y-n-j G:i'), $this->schedule["end"]->format('Y-n-j G:i'));
            if ($bind_result === false)
                throw new mysqli_sql_exception('bind');
            $execute_result = mysqli_stmt_execute($stmt);
            if ($execute_result === false)
                throw new mysqli_sql_exception('execute');
            $close_result = mysqli_stmt_close($stmt);
            if ($close_result === false)
                throw new mysqli_sql_exception('close');
                
                // user_schedule_relationsのインサート
            $stmt = mysqli_prepare($this->connection, "INSERT INTO user_schedule_relations (user_id, schedules_id, created_at, update_at) values(?, ?, now(), now())");
            if ($stmt === false)
                throw new mysqli_sql_exception('prepare');
            $bind_result = mysqli_stmt_bind_param($stmt, 'si', $this->user_id, mysqli_insert_id($this->connection));
            if ($bind_result === false)
                throw new mysqli_sql_exception('bind');
            $execute_result = mysqli_stmt_execute($stmt);
            if ($execute_result === false)
                throw new mysqli_sql_exception('execute');
            $close_result = mysqli_stmt_close($stmt);
            if ($close_result === false)
                throw new mysqli_sql_exception('close');
        } catch (mysqli_sql_exception $e) {
            $this->setErrorMessage('SQLエラー：' . $e->getMessage() . '-' . mysqli_error($this->connection));
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
            $stmt = mysqli_prepare($this->connection, "UPDATE user_schedule_relations SET deleted_at = now() where user_id = ? AND schedules_id = ?");
            if ($stmt === false)
                throw new mysqli_sql_exception('prepare');
            $bind_result = mysqli_stmt_bind_param($stmt, 'ss', $this->user_id, $this->schedule["view_id"]);
            if ($bind_result === false)
                throw new mysqli_sql_exception('bind');
            $execute_result = mysqli_stmt_execute($stmt);
            if ($execute_result === false)
                throw new mysqli_sql_exception('execute');
            $close_result = mysqli_stmt_close($stmt);
            if ($close_result === false)
                throw new mysqli_sql_exception('close');
            
            $stmt = mysqli_prepare($this->connection, "UPDATE schedules SET deleted_at = now() where schedules_id = ?");
            if ($stmt === false)
                throw new mysqli_sql_exception('prepare');
            $bind_result = mysqli_stmt_bind_param($stmt, 's', $this->schedule["view_id"]);
            if ($bind_result === false)
                throw new mysqli_sql_exception('bind');
            $execute_result = mysqli_stmt_execute($stmt);
            if ($execute_result === false)
                throw new mysqli_sql_exception('execute');
            $close_result = mysqli_stmt_close($stmt);
            if ($close_result === false)
                throw new mysqli_sql_exception('close');
        } catch (mysqli_sql_exception $e) {
            $this->setErrorMessage('SQLエラー：' . $e->getMessage() . '-' . mysqli_error($this->connection));
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
            $stmt = mysqli_prepare($this->connection, "UPDATE schedules SET title=?, detail=?, start_time=?, end_time=?, update_at=now() where schedules_id=?");
            if ($stmt === false)
                throw new mysqli_sql_exception('prepare');
            $bind_result = mysqli_stmt_bind_param($stmt, 'sssss', $this->schedule['title'], $this->schedule['detail'], $this->schedule['start']->format('Y-n-j G:i'), $this->schedule['end']->format('Y-n-j G:i'), $this->schedule['view_id']);
            if ($bind_result === false)
                throw new mysqli_sql_exception('bind');
            $execute_result = mysqli_stmt_execute($stmt);
            if ($execute_result === false)
                throw new mysqli_sql_exception('execute');
            $close_result = mysqli_stmt_close($stmt);
            if ($close_result === false)
                throw new mysqli_sql_exception('close');
        } catch (mysqli_sql_exception $e) {
            $this->setErrorMessage('SQLエラー：' . $e->getMessage() . '-' . mysqli_error($this->connection));
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
}
?>