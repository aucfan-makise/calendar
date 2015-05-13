<?php 
    require_once 'CalendarFunction.php';
    
    session_start();
    $week_day_name_array = CalendarFunction::getWeekDayNameArray();
    $calendar_function = new CalendarFunction();
?>
<!DOCTYPE html>
    <head>
        <title>Calendar</title>
        <link rel='stylesheet' type='text/css' href='./calendar.css'>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
        <script type="text/javascript" src="./calendar.js"></script>
    </head>
    <body>
        <?php if ($calendar_function->isError()): ?>
            <pre><?php echo 'エラー:'.$calendar_function->getErrorMessage(); ?></pre>
        <?php endif; ?>
        <?php if(isset($_SESSION['user'])): ?>
            User:<?php echo $_SESSION['user']; ?>
            <form method='post' action='login.php'>
                <input type='submit' name='logout' value='ログアウト'>
            </form>
        <?php else: ?>
            <a href='login.php'>ログイン</a>
            <a href='account_registration.php'>新規登録</a>
        <?php endif; ?>
        <button name='selected_date_before'>前</button>
        <button name='selected_date_next'>次</button>
            <!-- コンボボックス -->
            <p>
                <select name='selected_date_combo'>
                </select>
            週の始まり
            <select name='start_week_day'>
                <?php foreach ($week_day_name_array as $key => $value): ?>
                    <option value='<?php echo $key; ?>'<?php echo $calendar_function->isStartWeekDay($key) ? ' selected' : ''; ?>><?php echo $value; ?></option>
                <?php endforeach; ?>
            </select>
            </p>
        <form action='calendar.php' method='get'>
            <p>
            表示するカレンダーの数
                <input type='text' size=2 maxlength='2' name='calendar_size' value='<?php echo $calendar_function->getCalendarSize(); ?>'>
                <input type= 'submit' value='change'>
            </p>
        </form>
        
        <table id='calendar'>
        </table>
        <div id='schedule_form_div'>
            <form id='schedule_form'>
                <p>
                予定の編集<br>
                開始日
                    <select name='schedule_start_year'></select>年
                    <select name='schedule_start_month'></select>月
                    <select name='schedule_start_day'></select>日
                    <select name='schedule_start_hour'></select>時
                    <select name='schedule_start_minute'></select>分
                    <br>
               終了日
                    <select name='schedule_end_year'></select>年
                    <select name='schedule_end_month'></select>月
                    <select name='schedule_end_day'></select>日
                    <select name='schedule_end_hour'></select>時
                    <select name='schedule_end_minute'></select>分
                    <br>
                    タイトル:
                    <input type='text' size=10 maxlength='100' id='schedule_title' name='schedule_title'><br>
                    詳細  :
                    <input type='text' size=100 maxlength='500' id='schedule_detail' name='schedule_detail'>
                    
<!--                     <input type='submit' id='register' name='register' value='登録'> -->
                    <button id='register'>登録</button>
                    <input type='hidden' id='mode'>
                    <input type='hidden' id='view_id'>
                    <button id='modify'>修正</button>
                    <button id='delete'>削除</button>
<!--                     <input type='submit' id='modify' name='modify' value='修正'> -->
<!--                     <input type='submit' id='delete' name='delete' value='削除'> -->
                </p>
                <input type='hidden' name='token' value='
                    <?php echo $calendar_function->cryptSessionId(session_id()); ?>'>
            </form>
            <button id='schedule_form_close'>キャンセル</button>
            <div id='error_message'></div>
        </div>
        <div id='schedule_form_finish_div'>
            <div id='schedule_form_finish_message'></div>
            <button id='schedule_form_finish_div_close'>閉じる</button>
        </div>
    </body>
</html>