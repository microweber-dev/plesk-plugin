<?php
namespace MicroweberPackages\SharedServerScripts\FileManager\Adapters;

class PleskFileManager implements IFileManager
{
    /**
     * @var \pm_FileManager
     */
    public $fileManager;

    /**
     * @var
     */
    public $domainId;


    public function __construct()
    {
        $this->fileManager = new \pm_FileManager($this->domainId);
    }

    /**
     * @param $domainId
     * @return void
     */
    public function setDomainId($domainId)
    {
        $this->domainId = $domainId;
        $this->fileManager = new \pm_FileManager($this->domainId);
    }

    /**
     * @param $dir
     * @return mixed
     */
    public function isDir($dir)
    {
        return $this->fileManager->isDir($dir);
    }

    /**
     * @param $dir
     * @return mixed
     */
    public function isWritable($dir)
    {
        return $this->fileManager->isWritable($dir);
    }

}
