<?php
require_once 'CalendarFunction.php';
require_once 'ScheduleFunction.php';

session_start();

$calendar_function = new CalendarFunction($_SESSION, $_GET);
$schedule_function = new ScheduleFunction($_SESSION, $_GET, $_POST);
?>
<!DOCTYPE html>
    <head>
        <title>Schedule Edit</title>
        <?php if (isset($_SESSION['user'])): ?>
            <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <?php else: ?>
            <meta http-equiv='refresh' content='0;URL=login.php'>
        <?php endif; ?>
    </head>
    <body>
        <?php if (($schedule_function->isModified() && ! $calendar_function->isError())): ?>
            <?php if ($schedule_function->getModifyMode() === 'register') echo 'スケジュールを登録しました。'?>
            <?php if ($schedule_function->getModifyMode() === 'modify') echo 'スケジュールを編集しました。'?>
            <?php if ($schedule_function->getModifyMode() === 'delete') echo 'スケジュールを削除しました。'?>
            <br>
            <a href='calendar.php'>カレンダーに戻る</a>
        <?php else : ?>
            <?php if ($calendar_function->isError()): ?>
                <pre><?php echo 'エラー:'.$calendar_function->getErrorMessage(); ?></pre>
            <?php endif; ?>
            <?php if ($schedule_function->isError()): ?>
                <pre><?php echo 'エラー:'.$schedule_function->getErrorMessage(); ?></pre>
            <?php endif; ?>
            <form method='post' action='schedule_edit.php'>
    <!--          <form method='post' action='calendar.php?selected_date=<?php echo $calendar_function->getSelectedCalendar(); ?>'>-->
                <?php if ($calendar_function->isViewMode()): ?>
                    <?php $view_schedule = $schedule_function->getScheduleById(); ?>
                <?php endif; ?>
                <?php $selected_date = $calendar_function->getSelectedDateArray(); ?>
                <p>
                予定の編集<br>
                開始日
                    <select name='schedule_start_year'>
                        <?php foreach (range(2015, 2018) as $year): ?>
                            <option value='<?php echo $year; ?>'
                                <?php if (isset($_POST['schedule_start_year'])): ?>
                                    <?php echo $_POST['schedule_start_year'] == $year ? ' selected' : ''; ?>
                                <?php elseif ($calendar_function->isViewMode()): ?>
                                    <?php echo $view_schedule['start_time']['year'] == $year ? ' selected' : ''; ?>
                                <?php else: ?>
                                    <?php echo $selected_date['year'] == $year ? ' selected' : ''; ?>
                                <?php endif; ?>
                            ><?php echo $year; ?></option>
                        <?php endforeach; ?>  
                    </select>年
                    <select name='schedule_start_month'>
                        <?php foreach (range(1, 12) as $month): ?>
                            <option value='<?php echo $month; ?>'
                                <?php if (isset($_POST['schedule_start_month'])): ?>
                                    <?php echo $_POST['schedule_start_month'] == $month ? ' selected' : ''; ?>
                                <?php elseif ($calendar_function->isViewMode()): ?>
                                    <?php echo $view_schedule['start_time']['month'] == $month ? ' selected' : ''; ?>
                                <?php else: ?>
                                    <?php echo $selected_date['month'] == $month ? ' selected' : ''; ?>
                                <?php endif; ?>
                            ><?php echo $month; ?></option>
                        <?php endforeach; ?>
                    </select>月
                    <select name='schedule_start_day'>
                        <?php foreach (range(1, 31) as $day): ?>
                            <option value='<?php echo $day; ?>'
                                <?php if (isset($_POST['schedule_start_day'])): ?>
                                    <?php echo $_POST['schedule_start_day'] == $day ? ' selected' : ''; ?>
                                <?php elseif ($calendar_function->isViewMode()): ?>
                                    <?php echo $view_schedule['start_time']['day'] == $day ? ' selected' : ''; ?>
                                <?php else: ?>
                                    <?php echo $selected_date['day'] == $day ? ' selected' : ''; ?>
                                <?php endif; ?>
                            ><?php echo $day; ?></option>
                        <?php endforeach; ?>
                    </select>日
                    <select name='schedule_start_hour'>
                        <?php foreach (range(0, 23) as $hour): ?>
                            <option value='<?php echo $hour; ?>'
                            <?php if (isset($_POST['schedule_start_hour'])): ?>
                                    <?php echo $_POST['schedule_start_hour'] == $hour ? ' selected' : ''; ?>
                            <?php elseif ($calendar_function->isViewMode()): ?>
                                <?php echo $view_schedule['start_time']['hour'] == $hour ? ' selected' : ''; ?>
                            <?php else: ?>
                                <?php echo $calendar_function->isThisHour($hour) ? ' selected' : ''; ?>
                            <?php endif; ?>
                            ><?php echo $hour; ?></option>
                        <?php endforeach; ?>
                    </select>時
                    <select name='schedule_start_minute'>
                        <?php foreach (range(0, 59) as $minute): ?>
                          <option value='<?php echo $minute; ?>'
                            <?php if (isset($_POST['schedule_start_minute'])): ?>
                                <?php echo $_POST['schedule_start_minute'] == $minute ? ' selected' : ''; ?>
                            <?php elseif ($calendar_function->isViewMode()): ?>
                                <?php echo $view_schedule['start_time']['minute'] == $minute ? ' selected' : ''; ?>
                            <?php else: ?>
                                <?php echo $calendar_function->isThisMinute($minute) ? ' selected' : ''; ?>
                            <?php endif; ?>
                            ><?php echo $minute; ?></option>
                        <?php endforeach; ?>
                    </select>分<br>
               終了日
                    <select name='schedule_end_year'>
                        <?php foreach (range(2015, 2018) as $year): ?>
                            <option value='<?php echo $year; ?>'
                            <?php if (isset($_POST['schedule_end_year'])): ?>
                                <?php echo $_POST['schedule_end_year'] == $year ? ' selected' : ''; ?>
                            <?php elseif ($calendar_function->isViewMode()): ?>
                                <?php echo $view_schedule['end_time']['year'] == $year ? ' selected' : ''; ?>
                            <?php else: ?>
                                <?php echo $selected_date['year'] == $year ? ' selected' : ''; ?>
                            <?php endif; ?>
                            ><?php echo $year; ?></option>
                        <?php endforeach; ?>  
                    </select>年
                    <select name='schedule_end_month'>
                        <?php foreach (range(1, 12) as $month): ?>
                            <option value='<?php echo $month; ?>'
                            <?php if (isset($_POST['schedule_end_month'])): ?>
                                <?php echo $_POST['schedule_end_month'] == $month ? ' selected' : ''; ?>
                            <?php elseif ($calendar_function->isViewMode()): ?>
                                <?php echo $view_schedule['end_time']['month'] == $month ? ' selected' : ''; ?>
                            <?php else: ?>
                                <?php echo $selected_date['month'] == $month ? ' selected' : ''; ?>
                            <?php endif; ?>
                            ><?php echo $month; ?></option>
                        <?php endforeach; ?>
                    </select>月
                    <select name='schedule_end_day'>
                        <?php foreach (range(1, 31) as $day): ?>
                        <option value='<?php echo $day; ?>'
                            <?php if (isset($_POST['schedule_end_day'])): ?>
                                <?php echo $_POST['schedule_end_day'] == $day ? ' selected' : ''; ?>
                            <?php elseif ($calendar_function->isViewMode()): ?>
                                <?php echo $view_schedule['end_time']['day'] == $day ? ' selected' : ''; ?>
                            <?php else: ?>
                                <?php echo $selected_date['day'] == $day ? ' selected' : ''; ?>
                            <?php endif; ?>
                            ><?php echo $day; ?></option>
                        <?php endforeach; ?>
                    </select>日
                    <select name='schedule_end_hour'>
                        <?php foreach (range(0, 23) as $hour): ?>
                        <option value='<?php echo $hour; ?>'
                            <?php if (isset($_POST['schedule_end_hour'])): ?>
                                <?php echo $_POST['schedule_end_hour'] == $hour ? ' selected' : ''; ?>
                            <?php elseif ($calendar_function->isViewMode()): ?>
                                <?php echo $view_schedule['end_time']['hour'] == $hour ? ' selected' : ''; ?>
                            <?php else: ?>
                                <?php echo $calendar_function->isThisHour($hour) ? ' selected' : ''; ?>
                            <?php endif; ?>
                            ><?php echo $hour; ?></option>
                        <?php endforeach; ?>
                    </select>時
                    <select name='schedule_end_minute'>
                        <?php foreach (range(0, 59) as $minute): ?>
                        <option value='<?php echo $minute; ?>'
                            <?php if (isset($_POST['schedule_end_minute'])): ?>
                                <?php echo $_POST['schedule_end_minute'] == $minute ? ' selected' : ''; ?>
                            <?php elseif ($calendar_function->isViewMode()): ?>
                                <?php echo $view_schedule['end_time']['minute'] == $minute ? ' selected' : ''; ?>
                            <?php else: ?>
                                <?php echo $calendar_function->isThisMinute($minute) ? ' selected' : ''; ?>
                            <?php endif; ?>
                            ><?php echo $minute; ?></option>
                        <?php endforeach; ?>
                    </select>分<br>
                    タイトル:
                    <input type='text' size=10 maxlength='100' name='schedule_title' value='<?php 
                        if (isset($_POST['schedule_title'])){
                            echo $_POST['schedule_title'];
                        } elseif ($calendar_function->isViewMode()){
                            echo $calendar_function->e($view_schedule['title']);
                        } else{
                            echo "";
                        } ?>'><br>
                    詳細  :
                    <input type='text' size=100 maxlength='500' name='schedule_detail' value='<?php 
                        if (isset($_POST['schedule_detail'])){
                            echo $_POST['schedule_detail'];
                        } elseif ($calendar_function->isViewMode()){
                            echo $calendar_function->e($view_schedule['detail']);
                        } else{
                            echo "";
                        } ?>'>
                    <input type='submit' name='register' value='登録'>
                    
                    <?php if ($calendar_function->isViewMode()): ?>
                        <input type='hidden' name='view_id' value='<?php echo $calendar_function->getViewId(); ?>'>
                        <input type='submit' name='modify' value='修正'>
                        <input type='submit' name='delete' value='削除'>
                    <?php endif; ?>                    
                </p>
                <input type='hidden' name='token' value='
                    <?php echo $calendar_function->cryptSessionId(session_id()); ?>'>
            </form>
        <?php endif; ?>
    </body>
</html>