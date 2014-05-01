<?php
namespace Corog;

class Model {
	protected $_db = '';
	protected $_table = '';
	protected $_pk = 'id';
	public  function __construct() {
	}

	public function __set($name, $value) {
		$this->$name = $value;
	}

	public function __get($name) {
		return $this->$name;
	}	
}