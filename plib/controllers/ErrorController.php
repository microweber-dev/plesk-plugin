<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class ErrorController extends Modules_Microweber_BasepluginController
{
    public function init()
    {
        parent::init();
        $this->view->tabs = [];
    }

    public function indexAction()
    {
        $this->view->pageTitle = $this->_moduleName . ' - Error';
        $this->view->errorMessage = 'You don\'t have permissions to see this page.';
    }
}
