<?php
/**
 * Corog 
 * a lightweight framework for php development
 * @author York Liu <sadly@phpx.com>
 * @license The MIT License (MIT) , see LICENSE.txt
 * @link http://www.corog.com
 */
namespace Corog;
defined('COROG_ROOT') || define('COROG_ROOT',dirname(__FILE__));

class Corog {
	private static $_instance = null;
	private $_config;
	protected $routeInfo;

	final private function __construct() { 
		spl_autoload_register('Corog\Corog::classLoader');
		Handler::getInstance()->register();
	}

	final public function __destruct() {
	}

	final public static function getInstance () {
		if (null === self::$_instance)
			self::$_instance = new self();
		return self::$_instance;
	}

	final public function run() {
		$this->parseConfig();
		$this->parseRoute();
		$this->dispatch();
	}

	public static function getConfig ($keyName=null) {
		if (empty($keyName))
			return self::getInstance()->_config;
		else
			return self::getInstance()->_config[$keyName];
	}

	public static function getRouteInfo ($keyName=null) {
		if (empty($keyName))
			return self::getInstance()->routeInfo;
		else
			return self::getInstance()->routeInfo[$keyName];
	}

             public static function classLoader ($className) {
            		$className = str_replace( array('\\',  '_') ,  '/' , $className);
        		if (0 === strpos($className,'Corog/')) {
        			$fileName = dirname(COROG_ROOT).'/'.$className.'.php';
        			if (is_readable($fileName))
				require $fileName;
			else
				throw new Exception(' error loading core file '.$className);
		}
		else {
			$loader = function ($className, $config, $type) {
				if (false !== strpos($className, ucfirst($type))) {
        					$fileName = APP_ROOT .'/'. $config['_'.$type.'Dir'] .'/'. $className.'.php';
						if (is_readable($fileName)) {
						require $fileName;
						return true;
			             	}
					else
						throw new Exception(' error loading '.$type. ' file '.$className);
				} 
				else {
					return false;
				}

			};

			if (!(
			$loader($className, self::getConfig(), 'controller') ||
			$loader($className, self::getConfig(), 'model') ||
			$loader($className, self::getConfig(), 'view') ||
			$loader($className, self::getConfig(), 'helper') 
			)) {
        				$fileName = APP_ROOT.'/'.$className.'.php';
        				if (is_readable($fileName)) {
					require $fileName;
					return true;
			             }
				else
					throw new Exception(' error loading user defined file '.$className);
			}
		}
    	}
 
	private function parseRoute()  {
		$this->routeInfo = (new Router($this->_config))->parseRoute();
		return $this;
	}

	private function dispatch()  {
		self::classLoader($this->routeInfo['module']);
		$controller = new $this->routeInfo['module']() ;
		$func = array($controller, $this->routeInfo['action']);
          		if (!is_callable($func)) {
			throw new Exception(' action not found '.$this->routeInfo['action']);
            		}
		call_user_func($func);
	}

	private function parseConfig()  {
		$this->_config = require COROG_ROOT.'/Config.php';
		$appConfig = APP_ROOT.'/'.self::getConfig()['_configDir'].'/Config.php';
		if (is_readable($appConfig)) {
			$this->_config = array_merge($this->_config, require $appConfig);
		}
		return $this;
	}
	
	final private function __clone() {
	 }

}
