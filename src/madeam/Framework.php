<?php
namespace madeam;
/**
 * Madeam PHP Framework <http://madeam.com>
 * Copyright (c)  2009, Joshua Davey
 *                202-212 Adeliade St. W, Toronto, Ontario, Canada
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright    Copyright (c) 2009, Joshua Davey
 * @link        http://www.madeam.com
 * @package      madeam
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class Framework {

  
  /**
   * @var string
   */
  public static $requestUri = '/';
  
  /**
   * default request request
   * @var array
   */
  public static $requestrequest = array();
  
  public static $uriAppPath = '/';
  
  public static $uriPubPath = '/public/';
  
  public static $environment = false;
  
  public static $pathToPub = false;
  
  
  /**
   * 
   * @author Joshua Davey
   * @param string $environment
   * @param array $request
   * @param array $server
   */
  public static function setup($environment, $request, $server) {    
    // check for expected server parameters
    $diff = array_diff(array('DOCUMENT_ROOT', 'REQUEST_URI', 'QUERY_STRING', 'REQUEST_METHOD'), array_keys($server));
    if (!empty($diff)) {
      throw new exception\MissingExpectedParam('Missing expected server Parameter(s).');
    }
    
    // add ending / to document root if it doesn't exist -- important because it differs from unix to windows (or I think that's what it is)
    if (substr($server['DOCUMENT_ROOT'], - 1) != '/') { $server['DOCUMENT_ROOT'] .= '/'; }
    
    // set request request
    self::$requestrequest = $request;
      
    // set path to uri based on whether mod_rewrite is turned on or off.
    if (isset(self::$requestrequest['_uri'])) {
      self::$uriAppPath = self::cleanUriPath($server['DOCUMENT_ROOT'], self::$pathToPub);
      self::$requestUri = self::$requestrequest['_uri'] . '?' . $server['QUERY_STRING'];
    } else {
      self::$uriAppPath = self::dirtyUriPath($server['DOCUMENT_ROOT'], self::$pathToPub);
      $url = explode('index.php', $server['REQUEST_URI']);
      // check if it split into 2 peices.
      // If it didn't then there is an ending "index.php" so we assume there is no URI on the end either
      if (isset($url[1])) {
        self::$requestUri = $url[1];
      } else {
        self::$requestUri = '/';
      }
    }
    
    // determine the relative path to the public directory
    self::$uriPubPath = self::pubPath($server['DOCUMENT_ROOT'], self::$pathToPub);  
    
    // if the absolute path to the public directory can't be established based on the uriPubPath
    // we've derived then it's likely the developer is using symlinks to point to their project.
    // In this case we can't determine the paths.
    // Most likely the user has advanced priveledges and is able to set the DocumentRoot in the apache
    // config to point to "path/to/project/public/" and therefore all of our relative paths can be
    // set to "/".
    // 
    // Therefore if the developer is using symlinks they must point their DocumentRoot to Madeam's public
    // directory or everything will explode.
    if (!file_exists($server['DOCUMENT_ROOT'] . self::$uriPubPath)) {
      self::$uriPubPath = '/';
      self::$uriAppPath = '/';
    }
    
    // set layout if it hasn't already been set
    if (!isset(self::$requestrequest['_layout'])) { self::$requestrequest['_layout'] = 1; }
    
    // set overriding request method -- note: we need to get rid of all the $_SERVER references for testing purposes
    if (isset($server['X_HTTP_METHOD_OVERRIDE'])) {
      self::$requestrequest['_method'] = strtolower($server['X_HTTP_METHOD_OVERRIDE']);
    } elseif (isset(self::$requestrequest['_method']) && $server['REQUEST_METHOD'] == 'POST') {
      self::$requestrequest['_method'] = strtolower($request['_method']);
    } else {
      self::$requestrequest['_method'] = strtolower($server['REQUEST_METHOD']);
    }
    
    // check if this is an ajax call
    if (isset($server['HTTP_X_REQUESTED_WITH']) && $server['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
      self::$requestrequest['_ajax'] = 1;
    } else {
      self::$requestrequest['_ajax']  = 0;
    }
  }

  /**
   * dispatches all operations to controller specified by uri
   *
   * @return boolean
   * @author Joshua Davey
   */
  public static function dispatch() {
    
    // include routes
    require 'conf/routes.php';
    
    /**
     * This is messed up. I hate the way PHP handles the $_FILES array when using multidimensional arrays in your HTML forms
     */    
    if (isset($_FILES)) {
      $_files = array();
      foreach ($_FILES as $key => $fields) {
        $_files[$key] = array();
        foreach ($fields as $field => $files) {
          if (is_array($files)) {
            foreach ($files as $file => $value) {
              $_files[$key][$file][$field] = $value;
            }
          } else {
            $_files[$key] = $fields;
          }
        }
      }
    }
    
    self::$requestrequest = array_merge_recursive(self::$requestrequest, $_files);
    
    // make request
    $output = self::request(self::$requestUri, self::$requestrequest);
    
    // return output
    return $output;
  }

  /**
   * This is where all the magic starts.
   *
   * @param string $uri -- example: controller/action/32?foo=bar
   * @param array $request
   * @return string
   * @author Joshua Davey
   */
  public static function request($uri, $request = array()) {
    $request = Router::parse($uri, self::$uriAppPath, $request + array(
      '_controller' => 'index',
      '_action'     => 'index',
      '_format'     => 'html'
    ));
    
    return self::control($request);
  }
  
  
  /**
   * undocumented 
   *
   * @author Joshua Davey
   * @param array $request
   */
  public static function control($request) {    
    // because we allow controllers to be grouped into sub folders we need to recognize this when
    // someone tries to access them. For example if someone wants to access the 'admin/index' controller
    // they should be able to just type in 'admin' because 'index' is the default controller in that
    // group of controllers. To make this possible we check to see if a directory exists that is named
    // the same as the controller being called and then append the default controller name to the end
    // so 'admin' becomes 'admin/index' if the admin directory exists.
    // note: there is a consequence for this feature which means if you have a directory named 'admin'
    // you can't have a controller named 'Controller_Admin'
    if (is_dir('app/src/Controller/' . ucfirst($request['_controller']))) {
      $request['_controller'] .= '/' . 'index';
    }
    
    // set controller's class
    $request['_controller'] = preg_replace("/[^A-Za-z0-9_\-\/]/", null, $request['_controller']); // strip off the dirt
    $controllerClassNodes = explode('/', $request['_controller']);
    foreach ($controllerClassNodes as &$node) {
      $node = Inflector::camelize($node);
      $node = ucfirst($node);
    }
    
    // set controller class
    $controllerClass = implode('\\', $controllerClassNodes) . 'Controller';
    
    try {
      $controller = new $controllerClass($request);
    } catch (Exception\AutoloadFail $e) {
      if (is_dir(PROJECT_PATH . 'app/views/' . $request['_controller'])) {
        $view = $request['_controller'] . '/' . $request['_action'];
        $request['_controller'] = 'app';
        $controller = new \AppController($request);
        $controller->view($view);
      } elseif (is_file(PROJECT_PATH . 'app/views/' . $request['_controller'] . DS . $request['_action'] . '.' . $request['_format'])) {
        $view = $request['_controller'];
        $request['_action'] = $request['_controller'];
        $request['_controller'] = 'app';
        $controller = new \AppController($request);
        $controller->view($view);
      } else {
        // no controller or view found = critical error.
        header("HTTP/1.1 404 Not Found");
        Exception::catchException($e, array('message' => 'Missing Controller <strong>' . $controllerClass . "</strong> \n Create File: <strong>app/Controller/" . str_replace('_', DS, $controllerClass) . ".php</strong> \n <code>&lt;?php \n class $controllerClass extends Controller_App {\n\n  &nbsp; public function " . Inflector::camelize(lcfirst($request['_action'])) . "Action() {\n &nbsp;&nbsp;&nbsp; \n &nbsp; }\n\n   }</code>"));
      }
    }

    try {
      // process request
      $response = $controller->process();
      
      // delete controller
      unset($controller);

      // return response
      return $response;
    } catch (controller\exception\MissingAction $e) {
      header("HTTP/1.1 404 Not Found");
      Exception::catchException($e);
    } catch (controller\exception\MissingView $e) {
      header("HTTP/1.1 404 Not Found");
      Exception::catchException($e);
    }
  }
  
  
  /**
   * This method returns a clean base uri path.
   * 
   * /apache/document_root/website/  => /website/
   * /apache/document_root/          => /
   * 
   * @param $docRoot 
   * @param $publicPath
   * @author Joshua Davey
   */
  public static function cleanUriPath($docRoot, $publicPath) {
    return '/' . substr(str_replace(DS, '/', substr($publicPath, strlen($docRoot), -strlen(basename($publicPath)))), 0, -1);
  }
  
  /**
   * This method returns a base uri path but includes the "index.php" at the end, hence the dirty part. This is used
   * for sites that don't have mod_rewrite enabled and required the "index.php" at the end.
   * 
   * /apache/document_root/website/  => /website/index.php/
   * /apache/document_root/          => /index.php/
   * 
   * @param $docRoot 
   * @param $publicPath
   * @author Joshua Davey
   */
  public static function dirtyUriPath($docRoot, $publicPath) {
    return '/' . str_replace(DS, '/', substr(substr($publicPath, strlen($docRoot)), 0, -strlen(DS . basename($publicPath)))) . 'index.php/';
  }
  
  /**
   * This method returns the relative path to the public directory
   * 
   * /apache/document_root/website/  => /website/public/
   * /apache/document_root/          => /public/
   * 
   * @param $docRoot 
   * @param $publicPath
   * @author Joshua Davey
   */
  public static function pubPath($docRoot, $publicPath) {
    return '/' . str_replace(DS, '/', substr($publicPath, strlen($docRoot)));
  }

  /**
   * Enter description here...
   *
   * @param string $url
   * @param boolean $exit
   * @author Joshua Davey
   */
  public static function redirect($url, $exit = true) {
    if (! headers_sent()) {
      header('Location:  ' . self::url($url));
      if ($exit) {
        exit();
      }
    } else {
      throw new Exception\HeadersSent('Tried redirecting when headers already sent. (Check for echos before redirects)');
    }
  }
  
  /**
   * This method is used for creating application urls and external urls.
   * For the examples below assume the website is located at "apache/htdocs/website/"
   * 
   * URL:
   * posts/show         => /website/posts/show/
   * 
   * Relative URL: (beings with /)
   * /imgs/header.png   => /website/public/imgs/header.png
   * 
   * External URL: (beings with a protocol)
   * http://example.com => http://example.com
   *
   * @param string $url
   * @return string
   * @author Joshua Davey
   */
  public static function url($url) {
    if ($url == null || $url == '/') {
      return self::$uriAppPath;
    }

    if (substr($url, 0, 1) != "#") {
      if (substr($url, 0, 1) == '/') {
        $url = self::$uriPubPath . substr($url, 1, strlen($url));
      } elseif (! preg_match('/^[a-z]+:/', $url, $matchs)) {
        $url = self::$uriAppPath . $url;
      }
    }
    return $url;
  }

  
}