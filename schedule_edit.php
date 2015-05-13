<?php
require_once 'CalendarFunction.php';
require_once 'ScheduleFunction.php';

session_start();
$schedule_function = new ScheduleFunction($_SESSION, $_GET, $_POST);

$result = array();
if ($schedule_function->getModifyMode() === 'register') {
    $result['mode'] = 'register';
} elseif ($schedule_function->getModifyMode() === 'modify'){
    $result['mode'] = 'modify';
} elseif ($schedule_function->getModifyMode() === 'delete'){
    $result['mode'] = 'delete';
}

$schedule = array();
if (! $schedule_function->isModified() && ! $schedule_function->isError()){
    $schedule = $schedule_function->getScheduleById(); 
}

$result['result'] = ($schedule_function->isModified() && ! $schedule_function->isError()) ? true : false;
$result['schedule'] = $schedule;
if ($schedule_function->isError()) $result['error_message'] = 'エラー:'.$schedule_function->getErrorMessage();
echo json_encode($result);
?>