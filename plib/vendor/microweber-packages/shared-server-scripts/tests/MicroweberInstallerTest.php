<?php

namespace MicroweberPackages\SharedServerScripts;

use MicroweberPackages\SharedServerScripts\FileManager\Adapters\PleskServerFileManager;
use MicroweberPackages\SharedServerScripts\Shell\Adapters\PleskShellExecutor;
use PHPUnit\Framework\TestCase;
use MicroweberPackages\SharedServerScripts\FileManager\Adapters\NativeFileManager;
use MicroweberPackages\SharedServerScripts\Shell\Adapters\NativeShellExecutor;

class MicroweberInstallerTest extends TestCase
{
    public function testInstall()
    {
        $temp = dirname(__DIR__).'/temp';
        if (!is_dir($temp)) {
            mkdir($temp);
        }

        $targetPath = dirname(__DIR__).'/temp/my-microweber-installation';
        $sourcePath = dirname(__DIR__).'/temp/microweber-latest';

        $installer = new MicroweberInstaller();
        $installer->setPath($targetPath);
        $installer->setSourcePath($sourcePath);
        $installer->setFileManager(new NativeFileManager());
        $installer->setShellExecutor(new NativeShellExecutor());
        $installer->setAdminUsername('bobi_unittest');
        $installer->setAdminPassword('unitest-pass');
        $installer->setAdminEmail('bobi@unitest.com');
        $installer->setStandaloneInstallation();

        $status = $installer->run();

        $this->assertTrue($status['success']);
        $this->assertTrue(is_file($targetPath.'/config/app.php'));
        $this->assertTrue(is_file($targetPath.'/config/microweber.php'));


    }

}
