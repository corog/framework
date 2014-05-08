<?php
namespace Corog\DB;

class Mysqli {
	public $error;
	public $affectedRows = -1;
	public $insertId = -1;
	private static $_instance;
	private $_config;
	private $_rwType;
	private $_master;
	private $_slave;

	public function __construct($config) {
		$this->_config = $config;
		$this->_connect();
	}

	public function __destruct() {
		$this->_close();
	}

	public static function getInstance($config) {
		if (null == self::$_instance)
			self::$_instance = new self($config);
		return self::$_instance;
	}	

	private function _connect() {
		if(empty($this->_config['_rwType']))
		 	$this->_rwType = 'single'; 
		else
			$this->_rwType = $this->_config['_rwType']; 
		 
		 if('single' === $this->_rwType) {
		 	$this->_master =  $this->_realconnect($this->_config, 'master');
			$this->_slave = $this->_master;
		}

		 if('masterslave' === $this->_rwType) {
			$this->_master =  $this->_realconnect($this->_config['_master'], 'master');
			$this->_slave =  $this->_realconnect($this->_config['_slave'], 'slave');
		}	 
	}

	private function _realconnect($hostInfo, $serverType='master') {
			$tmpLink = new \mysqli($hostInfo['_host'],$hostInfo['_user'],$hostInfo['_passwd'],$hostInfo['_dbName']);
			if ($tmpLink->connect_errno)
				throw new \Corog\Exception('connection to '.$serverType.' database error '.$tmpLink->connect_error);
			return $tmpLink;
	}

	private function _close() {
		if('single' === $this->_rwType) {
			$this->_master->close();
			$this->_master = null;
			$this->_slave = null;
		}
		if('masterslave' === $this->_rwType) {
			$this->_master->close();
			$this->_master = null;
			$this->_slave->close();
			$this->_slave = null;
		}
	}

	public function bindDelete($query, $bindParams=null) {
		return $this->_query($this->_slave, $query, $bindParams, 'delete');
	}

	public function bindInsert($query, $bindParams=null) {
		return $this->_query($this->_slave, $query, $bindParams, 'insert');
	}

	public function bindUpdate($query, $bindParams=null) {
		return $this->_query($this->_slave, $query, $bindParams, 'update');
	}

	public function bindFind($query, $bindParams=null) {
		return $this->_query($this->_slave, $query, $bindParams, 'select');
	}

	private function _query($link, $query, $bindParams=null, $operation='select') {
		$flag = false;
		$res = false;
		$ret = false;
		if (null == $bindParams) {
			$res = $link->query($query, MYSQLI_STORE_RESULT);
			$flag = $res;
		}
		else {
			if ($statement = $link->prepare($query)) {
				$this->_bindParam($statement, $bindParams);
				$flag = $statement->execute();
				if ($flag)  
					$res = $statement->get_result();
			} 
			else {
				$this->error = 'failed to prepare statement '.$query.' '.$link->error;
				//throw New \Corog\Exception($this->error);
			}
		}
		if(false === $flag) {
			$this->error = 'failed to execute statement '.$query.' '.$link->error;
			//throw New \Corog\Exception($this->error);
		}
		if ($res instanceof \mysqli_result) {
			$result = $res->fetch_all(MYSQLI_ASSOC);

			$res->free();
			return $result;
		}
		if(true === $flag) {
			switch ($operation) {
				case 'update':
					$ret = true;
					$this->affectedRows = $link->affected_rows;
					break;
				case 'delete':
					$ret = true;
					$this->affectedRows = $link->affected_rows;
					break;
				case 'insert':
					$ret = true;
					$this->insertId = $link->insert_id;
					break;
			}
			if ($statement instanceof \mysqli_stmt) {
				$statement->close();
			}
		}
		return $ret;
	}

	private function _selectDb($link, $dbName) {
		return $link->select_db($dbName);
	}

	private function _bindParam(&$statement, $bindParams) {
		// Todo:  b format need setting by mysqli_stmt_send_long_data
			$arrParams = array($bindParams['format']);
			foreach ($bindParams['params'] as $key=>&$value) {
				$arrParams[] = $value;
			}
			$ref    = new \ReflectionClass('mysqli_stmt'); 
			$method = $ref->getMethod("bind_param"); 
			$method->invokeArgs($statement, $arrParams);	
	}
}