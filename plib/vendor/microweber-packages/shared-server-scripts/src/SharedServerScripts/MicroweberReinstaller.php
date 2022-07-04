<?php
namespace MicroweberPackages\SharedServerScripts;

use MicroweberPackages\SharedServerScripts\FileManager\Adapters\NativeFileManager;

class MicroweberReinstaller extends MicroweberInstaller {

    public function run()
    {
        return;
        
        // First we will make a directories
        foreach ($this->_getDirsToMake() as $dir) {
            if (!$this->fileManager->isDir($this->path . '/' . $dir)) {
                $this->fileManager->mkdir($this->path . '/' . $dir, '0755', true);
            }
        }

        foreach ($this->_getFilesForSymlinking() as $fileOrFolder) {

            $sourceDirOrFile = $this->sourcePath . '/' . $fileOrFolder;
            $targetDirOrFile = $this->path . '/' . $fileOrFolder;

            if ($this->type == self::TYPE_SYMLINK) {
                // Create symlink
                if ($this->fileManager->isFile($targetDirOrFile)) {
                    $this->fileManager->unlink($targetDirOrFile);
                } else {
                    $this->fileManager->rmdirRecursive($targetDirOrFile);
                }

                $this->fileManager->symlink($sourceDirOrFile, $targetDirOrFile);
            } else {
                if ($this->fileManager->isDir($sourceDirOrFile)) {

                    // if dir exist we will skip copy folder
                    if ($this->fileManager->isDir($targetDirOrFile)) {
                        $this->fileManager->rmdirRecursive($targetDirOrFile);
                    }

                    $this->fileManager->copyFolder($sourceDirOrFile, $targetDirOrFile);

                } else {
                    $this->fileManager->copy($sourceDirOrFile, $targetDirOrFile);
                }
            }
        }


        // And then we will copy folders
        foreach ($this->_getDirsToCopy() as $folder) {
            $sourceDir = $this->sourcePath .'/'. $folder;
            $targetDir = $this->path .'/'. $folder;
            if ($this->fileManager->isDir($targetDir)) {
                continue;
            }
            $this->fileManager->copyFolder($sourceDir, $targetDir);
        }

        // And then we will copy files
        foreach ($this->_getFilesForCopy() as $file) {
            $sourceFile = $this->sourcePath .'/'. $file;
            $targetFile = $this->path .'/'. $file;
            if ($this->fileManager->isFile($targetFile)) {
                continue;
            }
            $this->fileManager->copy($sourceFile, $targetFile);
        }

        $this->_chownFolders();

    }


}
