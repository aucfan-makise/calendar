<?php 
    require "CalendarFunction.php";

    $calendar_function = new CalendarFunction($_GET["selected_date"], $_GET["calendar_size"]);
?>
<html>
    <head>
        <title>Calendar</title>
        <link rel="stylesheet" type="text/css" href="./calendar.css">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body>
        <form method="get" action="calendar.php">
            <button name="selected_date" value="<?php echo date('Y-n', strtotime($calendar_function->getSelectedCalendar()." -1 month"))?>">前</button>
            <button name="selected_date" value="<?php echo date('Y-n', strtotime($calendar_function->getSelectedCalendar()." +1 month"))?>">次</button>
        </form>
            <!-- コンボボックス -->
        <form action="calendar.php" method="get">
            <p>
                <select name="selected_date">
                    <?php foreach ($calendar_function->getComboBoxArray() as $key => $value):?>
                    <option value="<?php echo $key?>"<?php echo $calendar_function->isSelectedCalendar($key) ? "selected" : ""?>><?php echo $value ?></option>
                    <?php endforeach;?>
                </select>
                <input type="submit" value="select">
            </p>
            <p>
            表示するカレンダーの数
                <input type="text" size=2 maxlength="2" name="calendar_size" value="<?php echo $calendar_function->getCalendarSize() ?>">
                <input type= "submit" value="change">
            </p>
        </form>
        <table id="calendar"><tr>
            <?php $calendar_num = 0?>
            <?php foreach ($calendar_function->getCalendarArray() as $year_array):?>
                <?php $year = array_search($year_array, $calendar_function->getCalendarArray())?>
                <?php foreach ($year_array as $month_array):?>
                
                    <?php $month = array_search($month_array, $year_array)?>
                    <?php if ($month_array["in_range"] === true):?>
                    <?php if ($calendar_num % 3 == 0 && $calendar_num != 0):?>
                        </tr><tr>
                    <?php endif;?>
                    <td valign="top"><table class="calendar_table">
                        <tr>
                            <td class="calendar_table_title" colspan="7">
                                <?php echo $year?>年<?php echo $month?>月
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
                        <?php $printing_calendar = $calendar_function->getMonthCalendarArray($year, $month)?>
                        
                        <?php foreach (($calendar_function->getMonthCalendarArray($year, $month)) as $day):?>
                                <?php if ($day["week_day"] == 0):?>
                                    <tr class="calendar_day_row">
                                <?php endif;?>
                                    
                                        <td class=<?php echo CalendarFunction::getTdClass($day)?>>
                                            <?php echo $day["day"]?>
                                            <?php echo CalendarFunction::isHoliday($day) ? " ".CalendarFunction::getHolidayName($day) : ""?>
                                        </td>
                                
                                <?php if ($day["week_day"] == 6):?>
                                    </tr>
                                    <tr>
                                    <?php foreach (range(0, 6) as $week_day):?>
                                        <td class="calendar_nomal_column">test</td>
                                    <?php endforeach;?>
                                    </tr>
                                <?php endif;?>
                        <?php endforeach;?>
                            </table></td>
                            <?php $calendar_num++;?>
                    <?php endif;?>
                <?php endforeach;?>
            <?php endforeach;?>
            </tr>
        </table>
    </body>
</html>