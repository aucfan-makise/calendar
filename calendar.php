<?php 
    require "CalendarFunction.php";

//     初期化 カレンダーの数
    $calendar_num = 3;
    
    //     表示するカレンダーの年月 Y-m
    $center_calendar;
    if(! is_null($_GET["pre"])) $center_calendar = $_GET["pre"];
    elseif(! is_null($_GET["next"])) $center_calendar = $_GET["next"];
    elseif(! is_null($_GET["date_select"])) $center_calendar = $_GET["date_select"];
    else $center_calendar = date("Y-n");
    
    //     カレンダーの数
    if(! is_null($_GET["calendarNum"])) $calendar_num = $_GET["calendarNum"];
//     カレンダーのループの開始と終わり
    $start_calendar = date("Y-n", strtotime($center_calendar." -".floor($calendar_num / 2)." month"));
    list($start_year, $start_month) = explode("-", $start_calendar);
    $end_calendar = date("Y-n", strtotime($center_calendar." +".ceil($calendar_num /2)." month -1 month"));
    list($end_year, $end_month) = explode("-", $end_calendar);
    
    $calendar_function = new CalendarFunction($start_year, $start_month, $end_year, $end_month);
    $calendar_array = $calendar_function->getCarendarArray();
    
    $calendar_td = array(
    		"today" => "calendar_today_column", 
    		"default" => "calendar_day_column", 
    		"0" => "calendar_sunday_column", 
    		"6" => "calendar_saturday_column", 
    		"public_holiday" => "calendar_public_holiday_column"
    );
?>
<html>
    <head>
        <title>Calendar</title>
        <link rel="stylesheet" type="text/css" href="./calendar.css">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body>
        <form method="get" action="calendar.php">
            <button name="pre" value="<?php echo date('Y-n', strtotime($center_calendar." -1 month"))?>">前</button>
            <button name="next" value="<?php echo date('Y-n', strtotime($center_calendar." +1 month"))?>">次</button>
            <!-- コンボボックス -->
            <p>
                <select name="date_select">
                    <?php foreach ($calendar_function->getComboBoxArray() as $key => $value):?>
                        <?php if ($key === $center_calendar):?>
                    <option value="<?php echo $key ?>" selected><?php echo $value ?></option>
                        <?php else: ?>
                    <option value="<?php echo $key ?>"><?php echo $value ?></option>
                        <?php endif; ?>
                    <?php endforeach;?>
                </select>
                <input type="submit" value="select">
            </p>
            <p>
            表示するカレンダーの数
                <input type="text" size=2 maxlength="2" name="calendarNum" value="<?php echo $calendar_num ?>">
                <input type= "submit" value="change">
            </p>
        </form>
        <table><tr>
            <?php for ($printing = $start_calendar; strtotime($printing) <= strtotime($end_calendar); $printing = date("Y-n", strtotime($printing." + 1 month"))):?>
                <?php list($year, $month) = explode("-", $printing)?>
                <?php if (date("n", strtotime($printing." - ".$start_calendar)) % 3 === 0):?>
                </tr><tr>
                <?php endif;?>
                <td><table class="calendar_table">
                    <tr>
                        <td class="calendar_table_title" colspan="7">
                            <?php echo $year."年".$month."月"?>
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
<!--         			前月 -->
                        <?php for ($before_month = CalendarFunction::getBeforeMonth($printing), $day = date("t", strtotime($year."-".$month." -1 month")) - $calendar_array[$year][$month][1]["week_day"] + 1; $day <= date("t", strtotime($year."-".$month." -1 month")); ++$day):?>
<!--                         日付の行 -->
                            <?php if ($calendar_function->isSunday($before_month["year"], $before_month["month"], $day)):?>
                                <tr class="calendar_day_row">
                            <?php endif;?>
                                
<!--                             日付のセル -->
                            <?php if ($calendar_function->isToday($before_month["year"], $before_month["month"], $day)):?>
                                <td class=<?php echo $calendar_td["today"]?>><?php echo $day?></td>
                            <?php elseif ($calendar_function->isHoliday($before_month["year"], $before_month["month"], $day)):?>
                                <td class=<?php echo $calendar_td["public_holiday"]?>><?php echo $day." ".$calendar_function->getHolidayName($before_month["year"], $before_month["month"], $day) ?></td>
                            <?php elseif ($calendar_function->isSunday($before_month["year"], $before_month["month"], $day)):?>
                                <td class=<?php echo $calendar_td["0"]?>><?php echo $day?></td>
                            <?php else:?>
                                <td class=<?php echo $calendar_td["default"]?>><?php echo $day?></td>
                            <?php endif;?>
                        <?php endfor;?>
                        
<!--         			今月 -->
                        <?php for ($day = 1; $day <= date('t', strtotime($year."-".$month)); ++$day):?>
<!--                         日付の行 -->
                            <?php if ($calendar_function->isSunday($year, $month, $day)):?>
                                <tr class="calendar_day_row">
                            <?php endif;?>
                                
<!--                             日付のセル -->
                            <?php if ($calendar_function->isToday($year, $month, $day)):?>
                                <td class=<?php echo $calendar_td["today"]?>><?php echo $day?></td>
                            <?php elseif ($calendar_function->isHoliday($year, $month, $day)):?>
                                <td class=<?php echo $calendar_td["public_holiday"]?>><?php echo $day." ".$calendar_function->getHolidayName($year, $month, $day) ?></td>
                            <?php elseif ($calendar_function->isSunday($year, $month, $day)):?>
                                <td class=<?php echo $calendar_td["0"]?>><?php echo $day?></td>
                            <?php elseif ($calendar_function->isSaturday($year, $month, $day)):?>
                                <td class=<?php echo $calendar_td["6"]?>><?php echo $day?></td>
                            <?php else:?>
                                <td class=<?php echo $calendar_td["default"]?>><?php echo $day?></td>
                            <?php endif;?>
                                
<!--                                 予定のセル -->
                            <?php if ($calendar_function->isSaturday($year, $month, $day)):?>
                            </tr>
                            <tr>
                                <?php for ($i = 0; $i < 7; ++$i):?>
                                <td class="calendar_nomal_column">test</td>
                                <?php endfor;?>
                            </tr>
                            <?php endif;?>
                        <?php endfor;?>
                            
<!--                         翌月 -->
                        <?php for ($next_month = CalendarFunction::getNextMonth($printing), $day = 1, $i = $calendar_array[$next_month["year"]][$next_month["month"]][1]["week_day"]; $i != 0 && $i <= 6; ++$day, ++$i):?>
<!--                             日付のセル -->
                            <?php if ($calendar_function->isToday($next_month["year"], $next_month["month"], $day)):?>
                                <td class=<?php echo $calendar_td["today"]?>><?php echo $day?></td>
                            <?php elseif ($calendar_function->isHoliday($next_month["year"], $next_month["month"], $day)):?>
                                <td class=<?php echo $calendar_td["public_holiday"]?>><?php echo $day." ".$calendar_function->getHolidayName($next_month["year"], $next_month["month"], $day) ?></td>
                            <?php elseif ($calendar_function->isSaturday($next_month["year"], $next_month["month"], $day)):?>
                                <td class=<?php echo $calendar_td["6"]?>><?php echo $day?></td>
                            <?php else:?>
                                <td class=<?php echo $calendar_td["default"]?>><?php echo $day?></td>
                            <?php endif;?>
                                
<!--                                 予定のセル -->
                            <?php if ($calendar_function->isSaturday($next_month["year"], $next_month["month"], $day)):?>
                            </tr>
                            <tr>
                                <?php for ($i = 0; $i < 7; ++$i):?>
                                <td class="calendar_nomal_column">test</td>
                                <?php endfor;?>
                            </tr>
                            <?php endif;?>
                        <?php endfor;?>
                        
                        </table></td>
            <?php endfor;?>
            </tr>
        </table>
    </body>
</html>