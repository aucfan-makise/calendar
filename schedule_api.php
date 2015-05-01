<?php 
require_once 'ScheduleFunction.php';
    try{
        $schedule_function = new ScheduleFunction(null, $_GET);
        $schedules = $schedule_function->getApiSchedule();
        session_start();
        $_SESSION = array();
        session_write_close();       
    } catch (Exception $e){
        echo $e->getMessage();
        return;
    }
//     xml
    if ($schedule_function->getApiFormat() === 'xml'){
        header('Content-type: text/xml; charset=utf-8');
        $xmlstr = '<?xml version="1.0" encoding="UTF-8" standalone="no"?><schedules></schedules>';
        $xml = new SimpleXMLElement($xmlstr);
        $xml->addChild('result_count', count($schedules));
        $xml_items = $xml->addChild('items');
        foreach ($schedules as $item) {
            $xml_item = $xml_items->addChild('item');
            $xml_item->addChild('id', $item['id']);
            $xml_item->addChild('title', $item['title']);
            $xml_item->addChild('detail', $item['detail']);
            $xml_item->addChild('start_time', $item['start_time']);
            $xml_item->addChild('end_time', $item['end_time']);
        }
        $dom = new DOMDocument('1.0');
        $dom->loadXML($xml->asXML());
        $dom->formatOutput = true;
        echo $dom->saveXML();
//         json
    } elseif ($schedule_function->getApiFormat() === 'json'){
        $json['schedules'] = array();
        $json['schedules']['result_count'] = count($schedules);
        $json['schedules']['items'] = $schedules;
        echo json_encode($json);
    }
?>