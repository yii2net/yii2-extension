<?php
namespace Openadm\Extension\Implement;

use Openadm\Extension\Interfaces\ComposerInterface;
use Openadm\Extension\Interfaces\ConfigSourceInterface;
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

    const CMD_REQUIRE  = "require";

    /**
     *
     */
    const CMD_REMOVE = "remove";

    const CMD_CONFIG = "config";

    /**
     * @var array
     */
    private $default_params = ["-n","--optimize-autoloader"];

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
        switch ($this->configSource->getLogLevel()){
            case ConfigSource::LOG_LEVEL_DEBUG:
                $this->default_params[] = "-vvv";
                break;
            case ConfigSource::LOG_LEVEL_INFO:
                $this->default_params[] = "-vv";
                break;
            default:
                $this->default_params[] = "";
                break;
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
        if($this->configSource->isDebug()){
            if(is_callable($callback))$callback($cmd . " in " .$this->configSource->getRootProjectPath());
        }
        $process = new Process($cmd,$this->configSource->getRootProjectPath(),$this->configSource->getShellEnv());
        $process->setTimeout(600);
        $process->start();
        $process->waitUntil(function ($type, $buffer)use($callback) {
            if(is_callable($callback))$callback($buffer);
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
        $params[] = "--prefer-dist";
        $this->exec(static::CMD_INSTALL, $params, $callback);
    }


    /**
     * @param array $params
     * @param $callback
     */
    public function update($params = [], $callback)
    {
        $params[] = "--prefer-dist";
        $this->exec(static::CMD_UPDATE, $params, $callback);
    }

    public function requireN($params = [], $callback)
    {
        array_unshift($params,"--prefer-dist");
        $this->exec(static::CMD_REQUIRE, $params, $callback);
    }

    /**
     * @param $packageName
     * @param $callback
     */
    public function remove($packageName, $callback)
    {
        $this->exec(static::CMD_REMOVE, [$packageName], $callback);
    }

    /**
     * @param array $params
     * @param $callback
     */
    public function config($params = [], $callback)
    {
        $this->exec(static::CMD_CONFIG, $params, $callback);
    }


}