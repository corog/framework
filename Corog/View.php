<?php
namespace Corog;

class View {
	private $_assignedValues = array();

	public function __construct() {
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
		$fileName = APP_ROOT.'/'.Corog::getInstance()->getConfig()['_viewDir'] .'/'. $viewName.'.php';
		if (is_readable($fileName)) {
			extract($this->_assignedValues);
			require $fileName;
		}
		else {
			throw new Exception(' error display template '.$fileName);
		}
	}
}