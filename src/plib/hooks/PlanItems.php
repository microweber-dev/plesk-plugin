<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_PlanItems extends pm_Hook_PlanItems
{
    public function getPlanItems()
    {
        return Modules_Microweber_Config::getPlanItems();
    }
}
