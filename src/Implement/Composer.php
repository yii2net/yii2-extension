<?php
namespace Yikaikeji\Extension\Implement;

use Yikaikeji\Extension\Interfaces\ComposerInterface;
use Yikaikeji\Extension\Interfaces\ConfigSourceInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Composer implements ComposerInterface
{
    /**
     *
     */
    const CMD_INSTALL = "install";

    /**
     *
     */
    const CMD_UPDATE  = "update";

    /**
     *
     */
    const CMD_REMOVE = "remove";

    /**
     * @var array
     */
    private $default_params = ["-vvv","-n"];

    /**
     * @var ConfigSourceInterface
     */
    private $configSource;

    /**
     * Composer constructor.
     * @param ConfigSourceInterface $configSource
     */
    public function __construct(ConfigSourceInterface $configSource)
    {
        $this->configSource = $configSource;
        if(!$this->canExecute()){
            throw new \UnexpectedValueException('Composer Path Config Error!');
        }
    }

    /**
     * @return bool
     */
    private function canExecute()
    {
        return is_file($this->getComposerPath()) && is_executable($this->getComposerPath());
    }

    /**
     * @return mixed
     */
    public function getComposerPath()
    {
        return $this->configSource->getComposerPath();
    }

    /**
     * @param $action
     * @param array $params
     * @return string
     */
    private function genCliCmd($action, $params = [])
    {
        $cmds = [
            $this->getComposerPath(),
        ];
        $cmds = array_merge($cmds,[$action],$params);
        return join(" ",$cmds);
    }

    /**
     * @param $action
     * @param array $params
     * @param $callback
     */
    private function exec($action, $params = [], $callback)
    {
        $cmd = $this->genCliCmd($action,array_merge($this->default_params,$params));
        if(is_callable($callback))$callback($cmd);
        $process = new Process($cmd,$this->configSource->getRootProjectPath());
        $process->setTimeout(600);
        $process->start();
        $process->waitUntil(function ($type, $buffer)use($callback) {
            if (Process::ERR === $type) {
                if(is_callable($callback))$callback($buffer);
            } else {
                if(is_callable($callback))$callback($buffer);
            }
        });
//        $cmd .= " -d " . $this->configSource->getRootProjectPath();
//        //echo $cmd;exit;
//        $handler = popen($cmd, 'r');
//        while (!feof($handler)) {
//            $output = fgets($handler,1024);
//            if(is_callable($callback))$callback($output);
//        }
//        pclose($handler);
    }


    /**
     * @param array $params
     * @param $callback
     */
    public function install($params = [], $callback)
    {
        $this->exec(static::CMD_INSTALL, $params, $callback);
    }


    /**
     * @param array $params
     * @param $callback
     */
    public function update($params = [], $callback)
    {
        $this->exec(static::CMD_UPDATE, $params, $callback);
    }


    /**
     * @param array $params
     * @param $callback
     */
    public function remove($packageName, $callback)
    {
        $this->exec(static::CMD_REMOVE, [$packageName], $callback);
    }



}