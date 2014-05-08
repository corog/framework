<?php
namespace Corog;

class Controller {
	private $_assignedValues = array();
	protected $view;

	public function __construct() {
	}

	public function model($modelName) {
		$fileName = APP_ROOT .'/'. Corog::getInstance()->getConfig()['_modelDir'] .'/'. $modelName.'Model.php';
		if (is_readable($fileName)) {
			$mName = $modelName.'Model';
			$model = new $mName($modelName);
			return $model;
		}
		else {
			$newModel = new Model($modelName);
			return $newModel;
		}
	}

	public function assign($key, $value=null) {
		if (is_array($key)) {
			$this->_assignedValues = array_merge($this->_assignedValues, $key);
		}
		else {
			$this->_assignedValues[$key] = $value;
		}
	}

	public function display($viewName = null) {
		if (is_null($viewName)) {
			$viewName = Corog::getInstance()->getRouteInfo()['view']; 
		}

		$this->view = new View();
		$this->view->assign($this->_assignedValues);
		$this->view->display($viewName);
	}

	public function apiResult() {
		die(json_encode($this->_assignedValues));
		//You can write your own method for data output,  msgpack is preferred in product enviorment.
		// die (msg_pack($this->_assignedValues));
	}
}