<?php
namespace Corog;

class Handler {
	private static $_instance; 
	protected $errors = array();
	final private function __construct() {
	}
	final private function __clone() {
	 }	

	final public static function getInstance() {
		if (null === self::$_instance)
			self::$_instance = new self();	
		return self::$_instance;
	}

	final public function register() {
		set_error_handler(array($this, '_errorHandler'));
		set_exception_handler(array($this, '_exceptionHandler'));
		register_shutdown_function(array($this, '_shutdownHandler'));
	}

	final public function _errorHandler($errno, $errstr, $errfile, $errline) {
		debug_print_backtrace();
		echo "<br>====Error:".$errstr;
	}

	final public function _exceptionHandler($exception) {
		debug_print_backtrace();
		echo "<br>====Exception:".$exception->getMessage();
	}

	final public function _shutdownHandler() {
		var_dump(error_get_last());
		echo "<br>====shutdown:"." script shutdown";
	}

}