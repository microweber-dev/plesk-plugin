<?php
/**
 * Created by PhpStorm.
 * User: Bojidar
 * Date: 7/27/2020
 * Time: 2:01 PM
 */

class Modules_Microweber_Log
{
    public static function debug($log) {

        \pm_Log::debug($log);
        file_put_contents('/tmp/microweber-log.txt', $log.PHP_EOL , FILE_APPEND | LOCK_EX);
    }
}