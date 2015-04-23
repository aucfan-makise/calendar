<?php
require_once 'CalendarFunction.php';
require_once 'ScheduleFunction.php';

$calendar_function = new CalendarFunction($_GET);
$schedule_function = new ScheduleFunction($_POST);
?>
<!DOCTYPE html>
    <head>
        <title>Schedule Edit</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body>
        <form method="post" action="calendar.php">
            <?php if ($calendar_function->isViewMode()): ?>
                <?php $view_schedule = $schedule_function->fetchScheduleById($calendar_function->getViewId()); ?>
            <?php endif; ?>
            <?php $selected_date = $calendar_function->getSelectedDateArray(); ?>
            <p>
            予定の編集<br>
            開始日
                <select name="schedule_start_year">
                    <?php foreach (range(2015, 2018) as $year): ?>
                        <option value="<?php echo $year; ?>"
                            <?php if ($calendar_function->isViewMode()): ?>
                                <?php echo $view_schedule["start_time"]["year"] == $year ? " selected" : ""; ?>
                            <?php else: ?>
                                <?php echo $selected_date["year"] == $year ? " selected" : ""; ?>
                            <?php endif; ?>
                        ><?php echo $year; ?></option>
                    <?php endforeach; ?>  
                </select>年
                <select name="schedule_start_month">
                    <?php foreach (range(1, 12) as $month): ?>
                        <option value="<?php echo $month; ?>"
                            <?php if ($calendar_function->isViewMode()): ?>
                                <?php echo $view_schedule["start_time"]["month"] == $month ? " selected" : ""; ?>
                            <?php else: ?>
                                <?php echo $selected_date["month"] == $month ? " selected" : ""; ?>
                            <?php endif; ?>
                        ><?php echo $month; ?></option>
                    <?php endforeach; ?>
                </select>月
                <select name="schedule_start_day">
                    <?php foreach (range(1, 31) as $day): ?>
                        <option value="<?php echo $day; ?>"
                            <?php if ($calendar_function->isViewMode()): ?>
                                <?php echo $view_schedule["start_time"]["day"] == $day ? " selected" : ""; ?>
                            <?php else: ?>
                                <?php echo $selected_date["day"] == $day ? " selected" : ""; ?>
                            <?php endif; ?>
                        ><?php echo $day; ?></option>
                    <?php endforeach; ?>
                </select>日
                <select name="schedule_start_hour">
                    <?php foreach (range(0, 23) as $hour): ?>
                        <option value="<?php echo $hour; ?>"
                        <?php if ($calendar_function->isViewMode()): ?>
                            <?php echo $view_schedule["start_time"]["hour"] == $hour ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $calendar_function->isThisHour($hour) ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $hour; ?></option>
                    <?php endforeach; ?>
                </select>時
                <select name="schedule_start_minute">
                    <?php foreach (range(0, 59) as $minute): ?>
                      <option value="<?php echo $minute; ?>"
                        <?php if ($calendar_function->isViewMode()): ?>
                            <?php echo $view_schedule["start_time"]["minute"] == $minute ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $calendar_function->isThisMinute($minute) ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $minute; ?></option>
                    <?php endforeach; ?>
                </select>分<br>
           終了日
                <select name="schedule_end_year">
                    <?php foreach (range(2015, 2018) as $year): ?>
                        <option value="<?php echo $year; ?>"
                        <?php if ($calendar_function->isViewMode()): ?>
                            <?php echo $view_schedule["end_time"]["year"] == $year ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $selected_date["year"] == $year ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $year; ?></option>
                    <?php endforeach; ?>  
                </select>年
                <select name="schedule_end_month">
                    <?php foreach (range(1, 12) as $month): ?>
                        <option value="<?php echo $month; ?>"
                        <?php if ($calendar_function->isViewMode()): ?>
                            <?php echo $view_schedule["end_time"]["month"] == $month ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $selected_date["month"] == $month ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $month; ?></option>
                    <?php endforeach; ?>
                </select>月
                <select name="schedule_end_day">
                    <?php foreach (range(1, 31) as $day): ?>
                    <option value="<?php echo $day; ?>"
                        <?php if ($calendar_function->isViewMode()): ?>
                            <?php echo $view_schedule["end_time"]["day"] == $day ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $selected_date["day"] == $day ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $day; ?></option>
                    <?php endforeach; ?>
                </select>日
                <select name="schedule_end_hour">
                    <?php foreach (range(0, 23) as $hour): ?>
                    <option value="<?php echo $hour; ?>"
                        <?php if ($calendar_function->isViewMode()): ?>
                            <?php echo $view_schedule["end_time"]["hour"] == $hour ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $calendar_function->isThisHour($hour) ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $hour; ?></option>
                    <?php endforeach; ?>
                </select>時
                <select name="schedule_end_minute">
                    <?php foreach (range(0, 59) as $minute): ?>
                    <option value="<?php echo $minute; ?>"
                        <?php if ($calendar_function->isViewMode()): ?>
                            <?php echo $view_schedule["end_time"]["minute"] == $minute ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $calendar_function->isThisMinute($minute) ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $minute; ?></option>
                    <?php endforeach; ?>
                </select>分<br>
                タイトル:
                <input type="text" size=10 maxlength="100" name="schedule_title"<?php echo $calendar_function->isViewMode() ? " value='".$view_schedule["title"]."'" : ""; ?>><br>
                詳細  :
                <input type="text" size=100 maxlength="500" name="schedule_detail"<?php echo $calendar_function->isViewMode() ? " value='".$view_schedule["detail"]."'" : ""; ?>>
                <input type="submit" name="register" value="登録">
                
                <?php if ($calendar_function->isViewMode()): ?>
                    <input type="hidden" name="view_id" value='<?php echo $calendar_function->getViewId(); ?>'>
                    <input type="submit" name="modify" value="修正">
                    <input type="submit" name="delete" value="削除">
                <?php endif; ?>                    
            </p>
        </form>
        <?php if ($calendar_function->isError()): ?>
            <pre><?php echo "エラー:".$calendar_function->getErrorMessage(); ?></pre>
        <?php endif; ?>
        <?php if ($schedule_function->isError()): ?>
            <pre><?php echo "エラー:".$schedule_function->getErrorMessage(); ?></pre>
        <?php endif; ?>
    </body>
</html>