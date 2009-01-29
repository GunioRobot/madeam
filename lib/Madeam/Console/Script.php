<?php

/**
 * Madeam :  Rapid Development MVC Framework <http://www.madeam.com/>
 * Copyright (c)	2006, Joshua Davey
 *								24 Ridley Gardens, Toronto, Ontario, Canada
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright		Copyright (c) 2006, Joshua Davey
 * @link				http://www.madeam.com
 * @package			madeam
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class Madeam_Console_Script {

  public $command_requirements = array();

  protected function createFile($file_name, $file_path, $file_content) {
    if (substr($file_path, - 1) !== DS) {
      $file_path = $file_path . DS;
    }
    
    $file = $file_path . $file_name;
    if (file_exists($file)) {
      if (Madeam_Console_CLI::getYN('The file ' . $file_name . ' already exists. Overwrite?') === false) {
        return false;
      }
    }
    
    if (file_put_contents($file, $file_content)) {
      Madeam_Console_CLI::outCreate('file ' . $file);
      return true;
    }
  }
  
  protected function createDir($dir) {
    if (!file_exists($dir)) {
      mkdir($dir);
      Madeam_Console_CLI::outCreate('directory ' . basename($dir));
    }
  }
  
}