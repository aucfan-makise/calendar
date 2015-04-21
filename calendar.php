<?php 
    require "CalendarFunction.php";

    $calendar_function = new CalendarFunction(
            $_GET["selected_date"], 
            $_GET["calendar_size"], 
            $_GET["register"],
            $_GET["modify"],
            $_GET["delete"],
            $_GET["schedule_start_year"], 
            $_GET["schedule_start_month"], 
            $_GET["schedule_start_day"],
            $_GET["schedule_start_hour"],
            $_GET["schedule_start_minute"],
            $_GET["schedule_end_year"],
            $_GET["schedule_end_month"],
            $_GET["schedule_end_day"],
            $_GET["schedule_end_hour"],
            $_GET["schedule_end_minute"],
            $_GET["schedule_title"],
            $_GET["schedule_detail"],
            $_GET["view_id"]);
?>
<!DOCTYPE html>
    <head>
        <title>Calendar</title>
        <link rel="stylesheet" type="text/css" href="./calendar.css">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body>
        <?php if ($calendar_function->isError()): ?>
            <pre><?php echo "エラー:".$calendar_function->getErrorMessage(); ?></pre>
        <?php endif; ?>
        
        <form method="get" action="calendar.php">
            <button name="selected_date" value="<?php echo date('Y-n', strtotime($calendar_function->getSelectedCalendar()." -1 month")); ?>">前</button>
            <button name="selected_date" value="<?php echo date('Y-n', strtotime($calendar_function->getSelectedCalendar()." +1 month")); ?>">次</button>
        </form>
            <!-- コンボボックス -->
        <form action="calendar.php" method="get">
            <p>
                <select name="selected_date">
                    <?php foreach ($calendar_function->getComboBoxArray() as $key => $value): ?>
                    <option value="<?php echo $key; ?>"<?php echo $calendar_function->isSelectedCalendar($key) ? " selected" : ""; ?>><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" value="select">
            </p>
            <p>
            表示するカレンダーの数
                <input type="text" size=2 maxlength="2" name="calendar_size" value="<?php echo $calendar_function->getCalendarSize(); ?>">
                <input type= "submit" value="change">
            </p>
        </form>
<!--             予定登録 -->
        <form method="get" action="calendar.php">
            <p>
            予定の編集<br>
            開始日
                <select name="schedule_start_year">
                    <?php foreach ($calendar_function->getScheduleYear() as $year): ?>
                        <option value="<?php echo $year; ?>"
                        <?php if ($calendar_function->isViewMode() && ! $calendar_function->getModifiedFlag()): ?>
                            <?php echo $calendar_function->getViewScheduleData("start_year") == $year ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $calendar_function->isTodaysYear($year) ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $year; ?></option>
                    <?php endforeach; ?>  
                </select>年
                <select name="schedule_start_month">
                    <?php foreach (range(1, 12) as $month): ?>
                        <option value="<?php echo $month; ?>"
                        <?php if ($calendar_function->isViewMode() && ! $calendar_function->getModifiedFlag()): ?>
                            <?php echo $calendar_function->getViewScheduleData("start_month") == $month ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $calendar_function->isTodaysMonth($month) ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $month; ?></option>
                    <?php endforeach; ?>
                </select>月
                <select name="schedule_start_day">
                    <?php foreach (range(1, 31) as $day): ?>
                        <option value="<?php echo $day; ?>"
                        <?php if ($calendar_function->isViewMode() && ! $calendar_function->getModifiedFlag()): ?>
                            <?php echo $calendar_function->getViewScheduleData("start_day") == $day ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $calendar_function->isTodaysDay($day) ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $day; ?></option>
                    <?php endforeach; ?>
                </select>日
                <select name="schedule_start_hour">
                    <?php foreach (range(0, 23) as $hour): ?>
                        <option value="<?php echo $hour; ?>"
                        <?php if ($calendar_function->isViewMode() && ! $calendar_function->getModifiedFlag()): ?>
                            <?php echo $calendar_function->getViewScheduleData("start_hour") == $hour ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $calendar_function->isThisHour($hour) ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $hour; ?></option>
                    <?php endforeach; ?>
                </select>時
                <select name="schedule_start_minute">
                    <?php foreach (range(0, 59) as $minute): ?>
                      <option value="<?php echo $minute; ?>"
                        <?php if ($calendar_function->isViewMode() && ! $calendar_function->getModifiedFlag()): ?>
                            <?php echo $calendar_function->getViewScheduleData("start_minute") == $minute ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $calendar_function->isThisMinute($minute) ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $minute; ?></option>
                    <?php endforeach; ?>
                </select>分<br>
           終了日
                <select name="schedule_end_year">
                    <?php foreach ($calendar_function->getScheduleYear() as $year): ?>
                        <option value="<?php echo $year; ?>"
                        <?php if ($calendar_function->isViewMode() && ! $calendar_function->getModifiedFlag()): ?>
                            <?php echo $calendar_function->getViewScheduleData("end_year") == $year ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $calendar_function->isTodaysYear($year) ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $year; ?></option>
                    <?php endforeach; ?>  
                </select>年
                <select name="schedule_end_month">
                    <?php foreach (range(1, 12) as $month): ?>
                        <option value="<?php echo $month; ?>"
                        <?php if ($calendar_function->isViewMode() && ! $calendar_function->getModifiedFlag()): ?>
                            <?php echo $calendar_function->getViewScheduleData("end_month") == $month ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $calendar_function->isTodaysMonth($month) ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $month; ?></option>
                    <?php endforeach; ?>
                </select>月
                <select name="schedule_end_day">
                    <?php foreach (range(1, 31) as $day): ?>
                    <option value="<?php echo $day; ?>"
                        <?php if ($calendar_function->isViewMode() && ! $calendar_function->getModifiedFlag()): ?>
                            <?php echo $calendar_function->getViewScheduleData("end_day") == $day ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $calendar_function->isTodaysDay($day) ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $day; ?></option>
                    <?php endforeach; ?>
                </select>日
                <select name="schedule_end_hour">
                    <?php foreach (range(0, 23) as $hour): ?>
                    <option value="<?php echo $hour; ?>"
                        <?php if ($calendar_function->isViewMode() && ! $calendar_function->getModifiedFlag()): ?>
                            <?php echo $calendar_function->getViewScheduleData("end_hour") == $hour ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $calendar_function->isThisHour($hour) ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $hour; ?></option>
                    <?php endforeach; ?>
                </select>時
                <select name="schedule_end_minute">
                    <?php foreach (range(0, 59) as $minute): ?>
                    <option value="<?php echo $minute; ?>"
                        <?php if ($calendar_function->isViewMode() && ! $calendar_function->getModifiedFlag()): ?>
                            <?php echo $calendar_function->getViewScheduleData("end_minute") == $minute ? " selected" : ""; ?>
                        <?php else: ?>
                            <?php echo $calendar_function->isThisMinute($minute) ? " selected" : ""; ?>
                        <?php endif; ?>
                        ><?php echo $minute; ?></option>
                    <?php endforeach; ?>
                </select>分<br>
                タイトル:
                <input type="text" size=10 maxlength="100" name="schedule_title"<?php echo $calendar_function->isViewMode() ? " value='".$calendar_function->getViewScheduleData("title")."'" : ""; ?>><br>
                詳細  :
                <input type="text" size=100 maxlength="500" name="schedule_detail"<?php echo $calendar_function->isViewMode() ? " value='".$calendar_function->getViewScheduleData("detail")."'" : ""; ?>>
                <input type="submit" name="register" value="登録">
                
                <?php if ($calendar_function->isViewMode() && ! $calendar_function->getModifiedFlag()): ?>
                    <input type="hidden" name="view_id" value='<?php echo $calendar_function->getViewId(); ?>'>
                    <input type="submit" name="modify" value="修正">
                    <input type="submit" name="delete" value="削除">
                <?php endif; ?>                    
            </p>
        </form>
        <table id="calendar">
            <tr><!-- 1 -->
            <?php $calendar_num = 0;
            foreach ($calendar_function->getCalendarArray() as $year_array):
                $year = array_search($year_array, $calendar_function->getCalendarArray());
                    foreach ($year_array as $month_array):
                        $month = array_search($month_array, $year_array);
                        if ($month_array["in_range"] === true):
                        if ($calendar_num % 3 == 0 && $calendar_num != 0): ?>
            </tr>
            <tr>
                    <?php endif; ?>
                <td valign="top"><table class="calendar_table">
                    <tr>
                        <td class="calendar_table_title" colspan="7">
                                <?php echo $year; ?>年<?php echo $month; ?>月
                        </td>
                    </tr>
                    <tr class="calendar_week_row">
                        <td class="calendar_week_column">Sun</td>
        				<td class="calendar_week_column">Mon</td>
        				<td class="calendar_week_column">Tue</td>
        				<td class="calendar_week_column">Wed</td>
        				<td class="calendar_week_column">Thu</td>
        				<td class="calendar_week_column">Fri</td>
        				<td class="calendar_week_column">Sat</td>
                    </tr>
                        <?php $printing_calendar = $calendar_function->getMonthCalendarArray($year, $month);
                        foreach (($calendar_function->getMonthCalendarArray($year, $month)) as $day):
                            if ($day["week_day"] == 0): ?>
                            <tr>
                            <?php endif; ?>
                                <td>
                                    <div class=<?php echo $day["div_class"]; ?>>
                                        <?php echo $day["day"];
                                        echo CalendarFunction::isHoliday($day) ? " ".CalendarFunction::getHolidayName($day) : ""; ?>
                                    </div>
                                    <div class="calendar_schedule_div">
                                        <?php foreach ($day["aucfan_topic"] as $topic): ?>
                                            <a href="<?php echo $topic["link"]; ?>"><?php echo $topic["title"]; ?></a>
                                        <?php endforeach; ?>
                                        <?php foreach ($day["schedules"] as $id => $schedule): ?>
                                            <a href="calendar.php?view_id=<?php echo $id; ?>&selected_date=<?php echo $calendar_function->getSelectedCalendar(); ?>"><?php echo $schedule; ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <?php if ($day["week_day"] == 6): ?>
                            </tr>
                                <?php endif; ?>
                        <?php endforeach; ?>
                </table>
                </td>
                        <?php $calendar_num++; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </tr>
        </table>
    </body>
</html>