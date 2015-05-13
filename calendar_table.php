<?php 
    require_once 'CalendarFunction.php';
    
    session_start();
    $week_day_array = CalendarFunction::getWeekDayNameArray();
    $calendar_function = new CalendarFunction();
?>
<tr>
<?php $calendar_num = 0;
foreach ($calendar_function->getCalendarArray() as $year_array):
$year = array_search($year_array, $calendar_function->getCalendarArray());
    foreach ($year_array as $month_array):
        $month = array_search($month_array, $year_array);
        $calendar_datetime = new DateTime($year.'-'.$month);
        if ($month_array['in_range'] === true):
        if ($calendar_num % 3 == 0 && $calendar_num != 0): ?>
</tr>
<tr>
    <?php endif; ?>
<td valign='top'><table class='calendar_table'>
    <tr>
        <td class='calendar_table_title' colspan='7'>
                <?php echo $calendar_datetime->format('Y年n月')?>
        </td>
    </tr>
    <tr class='calendar_week_row'>
        <?php foreach (range($calendar_function->getStartWeekDay(), 6) as $week_day_num): ?>
            <td class='calendar_week_column'><?php echo $week_day_name_array[$week_day_num]; ?></td>
        <?php endforeach; ?>
        <?php if ($calendar_function->getStartWeekDay() != 0): ?>
            <?php foreach (range(0, $calendar_function->getStartWeekDay() - 1) as $week_day_num): ?>
                <td class='calendar_week_column'><?php echo $week_day_name_array['$week_day_num']; ?></td>
            <?php endforeach; ?>
        <?php endif; ?>
    </tr>
        <?php foreach (($calendar_function->getMonthCalendarArray($calendar_datetime)) as $day):
            if ($day['week_day'] == $calendar_function->getStartWeekDay()): ?>
            <tr>
            <?php endif; ?>
                <td>
                    <div class=<?php echo $day['div_class']; ?>>
                        <a id='<?php echo implode('-', array($day['year'], $day['month'], $day['day'])); ?>' class='schedule_registration'></a>
                        <div>                                        
                            <?php echo $day['day'];
                            echo CalendarFunction::isHoliday($day) ? ' '.CalendarFunction::getHolidayName($day) : ''; ?>
                        </div>
                    </div>
                    <div class='calendar_schedule_div'>
                        <?php foreach ($day['aucfan_topic'] as $topic): ?>
                            <a href='<?php echo $topic['link_e']; ?>'><?php echo $calendar_function->e($topic['title_e']); ?></a>
                        <?php endforeach; ?>
                        <?php if (! is_null($day['schedules'])): ?>
                            <?php foreach ($day['schedules'] as $id => $schedule_array): ?>
                                <a class='schedule_link' id='<?php echo $id; ?>'>
                                <?php if ($schedule_array['start_time'] == '00:00' && $schedule_array['end_time'] == '23:59'): ?>
                                    <?php echo $calendar_function->e($schedule_array['title']); ?> 
                                <?php elseif ($schedule_array['end_time'] == '23:59'): ?>
                                    <?php echo $schedule_array['start_time']; ?>~ <?php echo $calendar_function->e($schedule_array['title']); ?>
                                <?php elseif ($schedule_array['start_time'] == '00:00'): ?>
                                    ~<?php echo $schedule_array['end_time']; ?> <?php echo $calendar_function->e($schedule_array['title']);?>
                                <?php else: ?>
                                    <?php echo $schedule_array['start_time']; ?>~<?php echo $schedule_array['end_time']; ?> <?php echo $calendar_function->e($schedule_array['title']); ?>
                                <?php endif; ?>
                                </a>
                                <br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </td>
                <?php if ($day['week_day'] == ($calendar_function->getStartWeekDay() + 6) % 7): ?>
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