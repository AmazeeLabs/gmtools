<?php namespace Amazee\GMT;

define("GMT_RSYNC_CMD", "/usr/bin/rsync");
define("GMT_MV_CMD", "/bin/mv");
define("GMT_LN_CMD", "/bin/ln");

class Utils
{
    public static function expandTilde($path)
    {
        if (function_exists('posix_getuid') && strpos($path, '~') !== false) {
            $info = posix_getpwuid(posix_getuid());
            $path = str_replace('~', $info['dir'], $path);
        }

        return $path;
    }

    public static function removeMultiSlash($path) {
      while( strpos($path,"//") !== FALSE ) {
          $path = preg_replace("|//|","/", $path);
      }

      return $path;
    }

    public static function processPath($path)
    {
      $path = self::expandTilde($path);
      $path = self::removeMultiSlash($path);

      return $path;
    }

    public static function checkDirectory($path, $createIfMissing = FALSE) {
        if(!is_dir($path) && $createIfMissing) {
          @mkdir($path);
        }

        return is_dir($path);
    }

    public static function outputHR($line = NULL) {
      echo "\n-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
      if($line)
      {
        echo " !!] " . $line . "\n";
      }
    }

    public static function runPassThru($cmd, $verbose = FALSE) {
      $return = 0;
      if($verbose) {
        self::outputHR($cmd);
      }
      
      passthru($cmd, $return);
      
      if($verbose) {
        self::outputHR();
      }

      return $return == 0; 
    }

    public static function rsyncRecursive($source, $destination, $verbose = FALSE, $exclude = array()) {
      $cmd = GMT_RSYNC_CMD ." -a ";
      if($verbose) {
        $cmd .= " -v --progress ";
      }

      if(is_array($exclude) && count($exclude)) {
        foreach($exclude as $ex) {
          $cmd .= " --exclude='".$ex."' ";
        }
      } else if(!is_array($exclude)) {
          $cmd .= " --exclude='".$exclude."' ";
      }
      
      $cmd .= " '".$source."' '".$destination."'";
      return self::runPassThru($cmd, $verbose);
    }

    public static function rsyncEmptyDirectory($source, $destination, $verbose = FALSE) {
      $cmd = GMT_RSYNC_CMD ." -a --delete ";
      if($verbose) {
        $cmd .= " -v --progress ";
      }

      $cmd .= " '".$source."' '".$destination."'";
      return self::runPassThru($cmd, $verbose);
    }

    public static function createAutoload($deployDir, $autoloadFile) {
      if(file_exists($autoloadFile)) {
        copy($autoloadFile, $autoloadFile."_backup_" . date('Y_m_d_H_i_s'));
      }

      $fileContents = "<?php\n\nreturn require '".self::processPath($deployDir."/vendor/autoload.php")."';\n\n";

      file_put_contents($autoloadFile, $fileContents);
    }

    public static function moveDirectory($source, $dest) {
      system(GMT_MV_CMD . " '".$source."' '".$dest."'");
    }

    public static function symlinkDir($source, $dest, $removeIfExists = FALSE) {
      $source = rtrim($source,'/');
      $dest = rtrim($dest,'/');

      if(file_exists($dest) && $removeIfExists) {
        unlink($dest);
      }

      system(GMT_LN_CMD . " -s '".$source."' '".$dest."'");
    }
}
