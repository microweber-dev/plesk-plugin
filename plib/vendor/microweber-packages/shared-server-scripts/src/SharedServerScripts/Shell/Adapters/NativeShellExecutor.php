<?php
namespace MicroweberPackages\SharedServerScripts\Shell\Adapters;

use Symfony\Component\Process\Process;

class NativeShellExecutor implements IShellExecutor
{
    /**
     * @param string $file
     * @param array $args
     * @return mixed|string
     */
    public function executeFile(string $file, array $args)
    {
        $processArgs = [];
        $processArgs[] = $file;
        $processArgs = array_merge($processArgs, $args);

        return $this->executeCommand($processArgs);
    }

    public function executeCommand(array $args)
    {
        $process = new Process($args);
        $process->setTimeout(100000);
        $process->mustRun();

        return $process->getOutput();
    }
}
