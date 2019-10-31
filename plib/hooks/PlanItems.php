<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH.

class Modules_Microweber_PlanItems extends pm_Hook_PlanItems
{
    public function getPlanItems()
    {
        return Modules_Microweber_Config::getPlanItems();
    }
}
