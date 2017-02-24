<?php namespace Amazee\GMT;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;

abstract class GMTCommandBase extends Command
{

    const GMT_CONFIG_FILE_ERROR = 255;
    const GMT_CONFIG_FILE_DEFAULT = "~/gmtools.yml";

    protected $config = [];
    protected $output = NULL;

    protected function setOutput($output) {
      $this->output = $output;
    }

    protected function logln($line, $type = "INFO", $time = NULL) {
      if(!$time || !is_numeric($time)) {
        $time = time();
      }

      $typeStr = '';
      if($type) {
        $typeStr = $type  . ' ' ;
      }
      
      $timeStr = date('Y-m-d H:i:s', $time);
      $this->output->writeln($timeStr . '] ' . $typeStr . $line);
    }

    protected function configure()
    {
        $this->addOption('config','c',
          InputOption::VALUE_REQUIRED,
          'Specify a config file path',
          getcwd() . "/" . self::GMT_CONFIG_FILE_DEFAULT);
    }

    protected function prepareConfig($configFilePath = NULL, $requireConfigFile = TRUE)
    {
        if(is_null($configFilePath)) {
          $configFilePath = self::GMT_CONFIG_FILE_DEFAULT;
        }

        $configFilePath = Utils::processPath($configFilePath);

        if($requireConfigFile && !file_exists($configFilePath)) {
          throw new Exception($configFilePath . " not found");
        }

        if(file_exists($configFilePath)) {
          $this->config = Yaml::parse(file_get_contents($configFilePath));
        } else {
          $this->config = [];
        }

        $this->config['config'] = $configFilePath;
    }
}
