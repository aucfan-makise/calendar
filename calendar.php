<?php 
    require_once "CalendarFunction.php";

    $calendar_function = new CalendarFunction($_GET, $_POST);
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
        <table id="calendar">
            <tr>
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
                                        <a href="schedule_edit.php?selected_date=<?php echo $calendar_function->getSelectedCalendar()."-".$day["day"]; ?>"></a>
                                        <div>                                        
                                            <?php echo $day["day"];
                                            echo CalendarFunction::isHoliday($day) ? " ".CalendarFunction::getHolidayName($day) : ""; ?>
                                        </div>
                                    </div>
                                    <div class="calendar_schedule_div">
                                        <?php foreach ($day["aucfan_topic"] as $topic): ?>
                                            <a href="<?php echo $topic["link"]; ?>"><?php echo $topic["title"]; ?></a>
                                        <?php endforeach; ?>
                                        <?php if (! is_null($day["schedules"])): ?>
                                            <?php foreach ($day["schedules"] as $id => $schedule_array): ?>
                                                <a href="schedule_edit.php?view_id=<?php echo $id; ?>">
                                                <?php if ($schedule_array["start_time"] == "00:00" && $schedule_array["end_time"] == "23:59"): ?>
                                                    <?php echo $schedule_array["title"]; ?> 
                                                <?php elseif ($schedule_array["end_time"] == "23:59"): ?>
                                                    <?php echo $schedule_array["start_time"]; ?>~ <?php echo $schedule_array["title"]; ?>
                                                <?php elseif ($schedule_array["start_time"] == "00:00"): ?>
                                                    ~<?php echo $schedule_array["end_time"]; ?> <?php echo $schedule_array["title"];?>
                                                <?php else: ?>
                                                    <?php echo $schedule_array["start_time"]; ?>~<?php echo $schedule_array["end_time"]; ?> <?php echo $schedule_array["title"]; ?>
                                                <?php endif; ?>
                                                </a>
                                                <br>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
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