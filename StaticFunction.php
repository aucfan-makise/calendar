<?php
class StaticFunction{
    public static function escape($str){
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}
?>