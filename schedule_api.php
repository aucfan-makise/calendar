<?php 
require_once 'ScheduleFunction.php';
    $schedule_function = new ScheduleFunction($_GET);
    $schedules = $schedule_function->getSchedulesArray();

    $xmlstr = '<?xml version="1.0" encoding="UTF-8" standalone="no"?><root></root>';
    $xml = new SimpleXMLElement($xmlstr);
    foreach ($schedules as $year => $month_array){
        foreach ($month_array as $month => $day_array){
            if ($month === 0) continue;
            foreach ($day_array as $day => $schedule){
                if ($day === 0) continue;
                foreach ($schedule as $items){
                    $xmlitem = $xml->addChild("item");
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