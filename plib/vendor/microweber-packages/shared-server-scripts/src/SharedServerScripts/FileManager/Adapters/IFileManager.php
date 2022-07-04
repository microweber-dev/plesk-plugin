<?php

namespace MicroweberPackages\SharedServerScripts\FileManager\Adapters;

interface IFileManager
{
    /**
     * @param string $dir
     * @return mixed
     */
    public function isDir(string $dir);

    /**
     * @param string $dir
     * @return mixed
     */
    public function isWritable(string $dir);

}
