<?php
namespace Corog;

class Router {
	private $_config;
	protected $routeInfo;

	public function __construct ($config) {
		$this->_config = $config;
	}
	
	public function parseRoute()  {
		if (isset($_SERVER['PATH_INFO'])) {
			$pathInfos = explode('/' , $_SERVER['PATH_INFO']);
			if (!empty($pathInfos[1])) {
				$this->routeInfo['module'] = trim( $pathInfos[1] );
				array_shift($pathInfos);
			}
			else
				$this->routeInfo['module'] = trim( $this->_config['_defaultModule'] );
			
			if (!empty($pathInfos[1])) {
				$this->routeInfo['action'] = trim( $pathInfos[1] );
				array_shift($pathInfos);
			}
			else
				$this->routeInfo['action'] = trim( $this->_config['_defaultAction'] );

			$params = array();
			$loopCount = count($pathInfos);
			for ($i = 0; $i<$loopCount; $i+=2) {
				$params[ $pathInfos[ $i ] ] = isset( $pathInfos[$i+1] ) ? $pathInfos[$i+1] : '';
			}
		}
		else {
			if (!empty($_GET['m']))
				$this->routeInfo['module'] = trim( $_GET['m'] );
			else
				$this->routeInfo['module'] = trim( $this->_config['_defaultModule'] );
			
			if (!empty($_GET['a']))
				$this->routeInfo['action'] = trim( $_GET['a'] );
			else
				$this->routeInfo['action'] = trim( $this->_config['_defaultAction'] );

			$params = array();
			foreach ($_GET as $key=> $value) {
				$params[ $key ] = $value;
			}

		}

		$this->routeInfo['module'] = trim($this->routeInfo['module'], "\r\n\t\0.\\\/") ;
		$this->routeInfo['action'] = trim($this->routeInfo['action'], "\r\n\t\0.\\\/") ;
		$this->routeInfo['view'] = ucfirst($this->routeInfo['module'])  . '_' . ucfirst($this->routeInfo['action']);
		$this->routeInfo['module'] = ucfirst($this->routeInfo['module'])  . 'Controller';
		$this->routeInfo['action'] = lcfirst($this->routeInfo['action'])  . 'Action';
		$this->routeInfo['params'] = $params;
		$this->routeInfo['params'] = array_merge($this->routeInfo['params'], $_POST);
		return $this->routeInfo;
	}

}