<?php 
require_once 'ScheduleFunction.php';
    header("Content-type: text/xml; charset=utf-8");
    $schedule_function = new ScheduleFunction($_GET);
    $schedules = $schedule_function->getSchedulesArray();

    $xmlstr = '<?xml version="1.0" encoding="UTF-8" standalone="no"?><schedules></schedules>';
    $xml = new SimpleXMLElement($xmlstr);
    foreach ($schedules as $year => $month_array){
        foreach ($month_array as $month => $day_array){
            if ($month === 0) continue;
            foreach ($day_array as $day => $schedule){
                if ($day === 0) continue;
                foreach ($schedule as $id => $items){
                    $xmlitem = $xml->addChild("item");
                    $xmlitem->addChild("id", $id);
                    $xmlitem->addChild("year", $year);
                    $xmlitem->addChild("month", $month);
                    $xmlitem->addChild("day", $day);
                    foreach ($items as $key => $value){
                        $xmlitem->addChild($key, $value);
                    }
                }
            }
        }
    }
    $dom = new DOMDocument('1.0');
    $dom->loadXML($xml->asXML());
    $dom->formatOutput = true;
    echo $dom->saveXML();