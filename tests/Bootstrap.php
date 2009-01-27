<?php
// set error reporting level
  error_reporting(E_ALL);

// define test constants
  define('TESTS_MADEAM_PUB_DIRECTORY',    realpath('tests/Madeam/_application/public') . DIRECTORY_SEPARATOR);
  define('TESTS_MADEAM_APP_DIRECTORY',    realpath('tests/Madeam/_application/app') . DIRECTORY_SEPARATOR);
  define('TESTS_MADEAM_ETC_DIRECTORY',    realpath('tests/Madeam/_application/etc') . DIRECTORY_SEPARATOR);
  define('TESTS_MADEAM_LIB_DIRECTORY',    realpath('lib') . DIRECTORY_SEPARATOR);
  define('TESTS_MADEAM_TESTS_DIRECTORY',  realpath('tests') . DIRECTORY_SEPARATOR);

// configure Madeam paths  
  $paths = array(
    TESTS_MADEAM_APP_DIRECTORY,   // application path
    TESTS_MADEAM_LIB_DIRECTORY,   // lib path
    TESTS_MADEAM_TESTS_DIRECTORY  // tests path
  );

// set include path
  set_include_path(implode(PATH_SEPARATOR, $paths) . PATH_SEPARATOR . get_include_path());
  
// include madeam (required for autoloader)
  require_once 'Madeam/Framework.php';

// reset error and exception handlers
  restore_error_handler();
  restore_exception_handler();

