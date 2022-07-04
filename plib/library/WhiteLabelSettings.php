<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_WhiteLabelSettings
{

    public static function get($key)
    {
        $client = pm_Session::getClient();

        if ($client->isReseller()) {
            $resellerPrefix = $client->getId();
            $key = $key . '_' . $resellerPrefix;
        }

        return pm_Settings::get($key);
    }

    public static function set($key, $value)
    {
        $client = pm_Session::getClient();

        if ($client->isReseller()) {
            $resellerPrefix = $client->getId();
            $key = $key . '_' . $resellerPrefix;
        }

        return pm_Settings::set($key, $value);
    }
}