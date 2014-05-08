<?php
namespace Corog;

class Model {
	protected $driver = null;
	private $_db = '';
	private $_table = '';
	private $_pk = 'id';
	private $_query = '';
	private $_field = '*';
	private $_where = null;
	private $_whereBind = null;	
	private $_limit = array('offset'=>0,'rowCount'=>1000);
	private $_order = null;
	private $_having = null;
	private $_join = null;
	private $_union = null;
	private $_group = null;
	private $_data = null;
	private $_bindparams = null;

	public  function __construct($modelName) {
		$this->_table = $modelName;
		$dbConfig = Corog::getInstance()->getConfig()['_dataBase'];
		$dbClass = 'Corog\\DB\\'.ucfirst($dbConfig['_driverType']);
		$this->driver = $dbClass::getInstance($dbConfig);
	}

	public function __set($name, $value) {
		$this->$name = $value;
	}

	public function __get($name) {
		return $this->$name;
	}	
	
	/*
		eg:
		$obj->table('tableName as aliasName, tableName2 ... ') 
		$obj->table(array('tableName'=>'aliasName', 'tableName2', ...))
	*/
	public function table($tableName) {
		if (is_string($tableName))
			$this->_table = $tableName;
		elseif (is_array($tableName)) {
			$tmpArr = array();
			foreach ($tableName as $key => $value) {
				if (is_string($key))
					$tmpArr[] = $key.'  as  '.$value;
				else
					$tmpArr[] = $value;
			}
			$this->_table = implode(',',  $tmpArr);
		}
		return $this;
	}

	/*
		eg:
		$obj->field() 
		$obj->field('*')
		$obj->field('fieldName1, fieldName2 as aliasName')
		$obj->field(array('fieldname1', 'fieldName2'=>'aliasName'))
	*/
	public function field($field = '*') {
		if (is_string($field))
			$this->_field = $field;
		elseif (is_array($field)) {
			$tmpArr = array();
			foreach ($field as $key => $value) {
				if (is_string($key))
					$tmpArr[] = $key.'  as  '.$value;
				else
					$tmpArr[] = $value;
			}
			$this->_field = implode(',',  $tmpArr);
		}
		return $this;
	}

	/*
		eg:
		$obj->orderBy('order1, order2 desc')
		$obj->orderBy(array('order1', 'order2'=>'desc'))
	*/
	public function orderBy($order) {
		if (is_string($order))
			$this->_order = $order;
		elseif (is_array($order)) {
			$tmpArr = array();
			foreach ($order as $key => $value) {
				if (is_string($key))
					$tmpArr[] = $key.'  '.$value;
				else
					$tmpArr[] = $value.' asc ';
			}
			$this->_order = implode(',',  $tmpArr);
		}		
		return $this;
	}

	public function groupBy($group) {
		if (is_string($group))
			$this->_group = $group;
		return $this;
	}

	public function having($having) {
		if (is_string($having))
			$this->_having = $having;
		return $this;
	}

	public function join($join) {
		if (is_string($join))
			$this->_join = $join;
		return $this;
	}

	public function union($union) {
		if (is_string($union))
			$this->_union = $union;
		return $this;
	}

	/*
		eg:
		$obj->where("username = 'york' and age>36 ");
		$obj->where("username = ? and age> ?", array( array('s','york') , array('i',36) ) );
	*/
	public function where($where, $bindInfo=null) {
		if (is_string($where)) {
			$this->_where = $where;
			if (is_array($bindInfo)) {
				$this->_whereBind = $bindInfo;
			}
		}
		elseif (is_array($where)) {
 			//this will be so complex
		}
		return $this;
	}

	public function clearData() {
		$this->data();
		return $this;
	}

	/*
	these data format is ( i:integer d: double s: string b: blob text )
	$obj->data( array('filedName'=>'value', 'filedName2'=>'value2') )
	$obj->data( array('filedName'=>array('datatype', 'value'),  'filedName2'=>array('datatype2', 'value2')) )
	*/

	public function data($data=null) {
		if (is_array($data)) {
			if(is_array($this->_data))
				$this->_data = array_merge($this->_data, $data);
			else
				$this->_data = $data;
		}
		elseif (null === $data)
			$this->_data = null;
		return $this;
	}

	public function limitCount($rowcount=1000) {
		if (is_int($rowcount))
			$this->_limit['rowCount'] = $rowcount;
		return $this;
	}

	public function limit($offset=0,$rowcount=1000) {
		if (is_int($offset))
			$this->_limit['offset'] = $offset;
		if (is_int($rowcount))
			$this->_limit['rowCount'] = $rowcount;
		return $this;
	}

	public function deleteAll() {
		return $this->delete(true);
	}

	public function delete($deleteAll = false) {
		if (true === $deleteAll)
			$this->toSql('deleteAll');
		else
			$this->toSql('delete');
		return $this->driver->bindDelete($this->_query, $this->_bindparams);
	}

	public function insert() {
		$this->toSql('insert');
		return $this->driver->bindInsert($this->_query, $this->_bindparams);
	}

	public function update() {
		$this->toSql('update');
		return $this->driver->bindUpdate($this->_query, $this->_bindparams);
	}

	public function find() {
		$this->toSql('find');
		return $this->driver->bindFind($this->_query, $this->_bindparams);
	}

	public function getError() {
		return $this->driver->error;
	}

	public function getAffectedRows() {
		return $this->driver->affectedRows;
	}

	public function getInsertId() {
		return $this->driver->insertId;
	}

	/*
		eg:
		$obj->rawFind("select * from tableName where username = 'york' and age>36 ");
		$obj->rawFind("select * from tableName where username = ? and age> ?", array( array('s','york') , array('i',36) ) );
	*/
	public function rawFind($query, $bindInfo=null) {
		if (is_string($query)) {
			$this->_query = $query;
			if (is_array($bindInfo)) {
				$this->_parseData($bindInfo);
			}
		}
		return $this->driver->bindFind($this->_query, $this->_bindparams);
	}

	private function toSql($dml) {
		$sql = '';
		switch(trim($dml)) {
			case 'delete':
				$sql = 'delete from '. $this->_addPart('table'). $this->_addPart('where'). $this->_addPart('limitCount');
				break;
			case 'deleteAll':
				$sql = 'delete from '. $this->_addPart('table'). $this->_addPart('where');
				break;
			case 'insert':
				$sql = 'insert into '. $this->_addPart('table'). $this->_addPart('data');
				break;
			case 'update':
				$sql = 'update '. $this->_addPart('table'). $this->_addPart('data'). $this->_addPart('where');
				break;
			case 'find':
				$sql = 'select'.$this->_addPart('field').
				                     ' from '.  $this->_addPart('table').
				                     $this->_addPart('join').
				                     $this->_addPart('where').
				                     $this->_addPart('groupBy'). 
				                     $this->_addPart('having'). 
				                     $this->_addPart('union'). 
				                     $this->_addPart('orderBy'). 
				                     $this->_addPart('limit');
				break;
		}
		$this->_query = $sql;
		return $sql;
	}

	private function _addPart($part) {
		$sql = '';
		switch(trim($part)) {
			case 'table':
				if (is_string($this->_table))
					$sql = '  '.$this->_table;
				break;
			case 'where':
				if (is_string($this->_where))
					$sql = ' where '.$this->_where;
				if(is_array($this->_whereBind))
					$this->_parseData($this->_whereBind);
				break;
			case  'data':
				if (is_array($this->_data)) {
					   $sql = $this->_parseData($this->_data);
				}
				break;
			case 'limitCount':
				if (is_int($this->_limit['rowCount'])) {
					$this->_parseData(array(  array('i',$this->_limit['rowCount']) ));
					$sql = ' limit ?';
				}
				break;
			case 'limit': // bad process , this is mysql ONLY syntax.
				if (is_int($this->_limit['offset']) && is_int($this->_limit['rowCount'])) {
					$this->_parseData(array(  array('i',$this->_limit['offset']), array('i', $this->_limit['rowCount'])) );
					$sql = ' limit ?, ?';
				}
				elseif (is_int($this->_limit['rowCount'])) {
					$this->_parseData(array(  array('i',$this->_limit['rowCount'])) );
					$sql = ' limit ?';
				}
				break;
			case 'field':
				if (is_string($this->_field))
					$sql = '  '.$this->_field;
				break;
			case 'join':
				if (is_string($this->_join))
					$sql = '  '.$this->_join;
				break;
			case 'groupBy':
				if (is_string($this->_group))
					$sql = '  '.$this->_group;
				break;
			case 'orderBy':
				if (is_string($this->_order))
					$sql = '  '.$this->_order;
				break;
			case 'having':
				if (is_string($this->_having))
					$sql = '  '.$this->_having;
				break;
			case 'union':
				if (is_string($this->_union))
					$sql = '  '.$this->_union;
				break;
		}
		return $sql;
	}

	private function _parseData($data) {
		$tmpArr = array();
		$tmpFormat = array();
		$tmpParam = array();
		foreach($data as $key => $value ) {
			if (is_scalar($value)) {
				$tmpArr[] = '  '.$key.' = ? ';
				switch(gettype($value)) {
					case 'boolean':
					case 'integer':
						$tmpParam[] = intval($value);
						$tmpFormat[] = 'i';
						break;
					case 'double':
						$tmpParam[] = floatval($value);
						$tmpFormat[] = 'd';
						break;
					case 'string':
						$tmpParam[] = strval($value);
						$tmpFormat[] = 's';
						break;
				}
			}
			elseif (is_array($value)) {
				$tmpArr[] = '  '.$key.' = ? ';
				switch($value[0]) {
					case 'i':
						$tmpParam[] = intval($value[1]);
						$tmpFormat[] = 'i';
						break;
					case 'd':
						$tmpParam[] = floatval($value[1]);
						$tmpFormat[] = 'd';
						break;
					case 's':
						$tmpParam[] = strval($value[1]);
						$tmpFormat[] = 's';
						break;
					case 'b': //blob means the data is binary.
						$tmpParam[] = $value[1];
						$tmpFormat[] = 'b';
						break;
				}
			}
		}

		$sql = ' set '.implode(',', $tmpArr);
		$this->_addParams($tmpFormat, $tmpParam);
		return $sql;
	}

	private function _addParams($tmpFormat, $tmpParam) {
		if (!is_array($this->_bindparams))
			$this->_bindparams = array('format'=>implode('', $tmpFormat), 'params'=>$tmpParam);
		else
			$this->_bindparams = array('format'=>$this->_bindparams['format']. implode('', $tmpFormat), 'params'=>array_merge($this->_bindparams['params'], $tmpParam));
		return $this;
	}

}