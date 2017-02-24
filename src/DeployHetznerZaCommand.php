<?php namespace Amazee\GMT;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class DeployHetznerZaCommand extends GMTCommandBase
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('deploy-io-to-hetzner-za')
            ->setDescription('Deploy an AmazeeIO dev site to Hetzner ZA Hosting')
            ->setHelp("This command will deploy an AmazeeIO Drupal site to Hetzner South Africa Hosting");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = time();

        try {
          self::prepareConfig($input->getOption('config'), FALSE);
          self::setOutput($output);

          if(!$this->config
           || !isset($this->config['hetznerpaths'])) {
            throw new Exception('The following config keys are required: hetznerpaths in ' . $this->config['config']);
          }
        } catch(Exception $ex) {
          $output->writeln('<error>Error processing the application config: ' . $ex->getMessage() . '</error>');
          return GMTCommandBase::GMT_CONFIG_FILE_ERROR;
        }
        
        $deployDir = Utils::processPath($this->config['hetznerpaths']['deploys'] . "/" . time());
        $deployDirSites = Utils::processPath($deployDir . "/sites");
        $deployDirWeb = Utils::processPath($deployDir . "/" . $this->config['hetznerpaths']['localwebpath']);
        $deployDirConf = Utils::processPath($deployDir . "/" . $this->config['hetznerpaths']['localconfpath']);
        $webrootDir = Utils::processPath($this->config['hetznerpaths']['webroot']);
        $webrootDirSitesDefault = Utils::processPath($webrootDir . "/sites/default");
        $emptyDir = Utils::processPath($this->config['hetznerpaths']['empty']);
        $devhost = Utils::processPath($this->config['hetznerpaths']['devhost']);
        $devhostDir = Utils::processPath($this->config['hetznerpaths']['devhostdir']);
        $devhostExclude = is_array($this->config['hetznerpaths']['exclude']) ? $this->config['hetznerpaths']['exclude'] : array();
        $devhostURL = $devhost . ":" . $devhostDir;
        $defaultsitefiles = isset($this->config['hetznerpaths']['defaultsitefiles']) ? $this->config['hetznerpaths']['defaultsitefiles'] : NULL;
        $hetznerDrupalConfigLocation = isset($this->config['hetznerpaths']['drupalconfig']) ? $this->config['hetznerpaths']['drupalconfig'] : NULL;
        $verbose = $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;
        $table = new Table($output);

        $tableRows = array(
            array('Config', $input->getOption('config')),
            array('Deploy Directory', $deployDir),
            array('Webroot Directory', $webrootDir),
            array('Deploy Copy Live Sites', $deployDirSites),
            array('Deploy Web', $deployDirWeb),
            array('Deploy Config', $deployDirConf),
            array('Current Sites Default', $webrootDirSitesDefault),
            array('Empty Directory', $emptyDir),
            array('Dev Hostname', $devhost),
            array('Dev Hostdir', $devhostDir),
            array('Dev Host URL', $devhostURL),
            array('Dev Host Exclude', implode(", ", $devhostExclude)),
            array('Config Location', $hetznerDrupalConfigLocation),
            array('Verbose', $verbose ? 'verbose' : 'normal'),
          );

        if($defaultsitefiles) {
          $tableRows[] = array('Default Site Files', $defaultsitefiles);
        }

        $table
          ->setHeaders(array('Key', 'Value'))
          ->setRows($tableRows);

        $table->render();

        // ------------- CHECK -------------------- 
        $this->logln("Checking deploy directory is created");
        if(!Utils::checkDirectory($deployDir, TRUE)) {
          $this->logln("Deploy directory failed", "ERROR");
          exit;
        }


        // ------------- CHECK -------------------- 
        if(!Utils::checkDirectory($deployDirSites, TRUE)) {
          $this->logln("Backup directory not found", "ERROR");
          exit;
        }


        // ------------- CHECK -------------------- 
        $this->logln("Checking webroot exits");
        if(!Utils::checkDirectory($webrootDir)) {
          $this->logln("Webroot directory failed", "ERROR");
          exit;
        }

        if(!Utils::checkDirectory($webrootDirSitesDefault)) {
          $this->logln("Existing sites/default not found", "ERROR");
          exit;
        }


        // ------------- CHECK -------------------- 
        $this->logln("Checking an empty directory exists (for fast recursive removal)");
        if(!Utils::checkDirectory($emptyDir, TRUE)) {
          $this->logln("Empty directory failed", "ERROR");
          exit;
        }


        // ------------- CHECK -------------------- 
        $this->logln("Checking devhost & devhostdir");
        if(!$devhost || !$devhostDir) {
          $this->logln("Dev Hostname and Dev Host Directory are required", "ERROR");
          exit;
        }


        // ------------- CHECK -------------------- 
        $this->logln("Checking config directory");
        if(!Utils::checkDirectory($hetznerDrupalConfigLocation, TRUE)) {
          $this->logln("Drupal config directory is required", "ERROR");
          exit;
        }


        // -------------- STEP --------------------
        $this->logln("Copy the live sites sites/default");
        if(!Utils::rsyncRecursive($webrootDirSitesDefault, $deployDirSites, $verbose)) {
          $this->logln("Problem copying the sites/default", "ERROR");
          exit;
        }


        // -------------- STEP --------------------
        $this->logln("Synchronize the dev site");
        if(!Utils::rsyncRecursive($devhostURL, $deployDir, $verbose, $devhostExclude)) {
          $this->logln("Problem synchronizing $devhostURL to $deployDir", "ERROR");
          exit;
        }


        // -------------- STEP --------------------
        $this->logln("Empty the existing webroot");
        if(!Utils::rsyncEmptyDirectory($emptyDir, $webrootDir, $verbose)) {
          $this->logln("Problem emptying $webrootDir using $emptyDir", "ERROR");
          exit;
        }


        // -------------- STEP --------------------
        $this->logln("Copy the new webroot");
        if(!Utils::rsyncRecursive($deployDirWeb, $webrootDir, $verbose)) {
          $this->logln("Problem synchronizing $deployDirWeb to $webrootDir", "ERROR");
          exit;
        }


        // -------------- STEP --------------------
        $this->logln("Synchronize the original live sites/default back");
        if(!Utils::rsyncRecursive($deployDirSites, $webrootDir, $verbose)) {
          $this->logln("Problem synchronizing $deployDirSites to $webrootDir", "ERROR");
          exit;
        }


        // -------------- STEP --------------------
        if(Utils::checkDirectory($defaultsitefiles)) {
          $this->logln("Copy in the default overrides");
          if(!Utils::rsyncRecursive($defaultsitefiles, $webrootDirSitesDefault, $verbose)) {
            $this->logln("Problem synchronizing $devhostURL to $deployDir", "ERROR");
            exit;
          }
        }


        // -------------- STEP --------------------
        $this->logln("Create a customized autoload.php pointing to the deploy directory");
        Utils::createAutoload($deployDir, Utils::processPath($webrootDir . "/autoload.php"));


        // -------------- STEP --------------------
        $this->logln("Symlink deploy directory web to the webroot");
        Utils::moveDirectory($deployDirWeb, Utils::processPath($deployDir."/web.backup")); 
        Utils::symlinkDir($webrootDir, $deployDirWeb); 


        // -------------- STEP --------------------
        $this->logln("Synchronize the config");
        Utils::rsyncRecursive(Utils::processPath($deployDirConf), $hetznerDrupalConfigLocation, TRUE); 


        // -------------- COMPLETE --------------------
        $endTime = time();
        $lengthTime = round(($endTime - $startTime) / 60, 2);
        $this->logln("DONE in " . $lengthTime . " seconds");
    }
}
