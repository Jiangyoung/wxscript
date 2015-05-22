<?php

class DbMysqli{
	/**
	 * 数据库连接
	 */
	protected $conn = null;

	/**
	 * 表前缀
	 */
	private $tbPrefix = '';

	final function __construct(){
		$dbConfig = (require "configs/db_config.php");
		$this->tbPrefix = $dbConfig['tbprefix'];
		$this->conn = @new \mysqli($dbConfig['host'],$dbConfig['user'],$dbConfig['passwd'],$dbConfig['dbname']);
		if(mysqli_connect_errno()){
			printf ( "Connect failed: %s\n" , mysqli_connect_error());
			exit();
		}
		if (! $this->conn -> set_charset ( "utf8" )) {
		     printf ( "Error loading character set utf8: %s\n" ,  $this->conn -> error );
		     exit();
		}
		$this->init();

	}

	public function setTbPrefix($tbPrefix){
		$this->tbPrefix = $tbPrefix;
	}

	protected function init(){

	}

	/**
	 * 基础查询操作
	 * @param String $sql  SQL查询语句
	 * @param Array $order  排序方式  'field'=>'DESC/ASC'
	 * @param Array/Integer $limit  区间 array(start,offset)  array(0,offset)直接写offset
	 * @return array
	 */
	function execute_dql($sql,$order=null,$limit=null)
	{
		//var_dump($sql);
		if(is_array($order)){
			$sql_order = '';
			$flag = 1;
			foreach ($order as $field => $sort) {
				if(1 != $flag++)$sql_order .= ',';
				$sql_order .= " `{$field}` {$sort} ";
			}
			$sql .= ' ORDER BY '.$sql_order;
		}
		if(is_array($limit)){
			if(is_numeric($limit[0]) && is_numeric($limit[1]) && intval($limit[0]) && intval($limit[1])){
				$sql .= " LIMIT {$limit[0]},{$limit[1]} ";
			}
		}else if(is_numeric($limit) && intval($limit)){
			$sql .= " LIMIT {$limit} ";
		}
		$queryRes = $this->conn->query($sql);
		$res = array();
		if($queryRes){
			while($row=$queryRes->fetch_assoc()){
				$res[] = $row;
			}
		}
		$queryRes->close();
		return $res;
	}

	/**
	 * 基础更新操作
	 * @param $sql
	 * @return bool|\mysqli_result
	 */
	function execute_dml($sql)
	{
		//var_dump($sql);
		$res = $this->conn->query($sql);
		return $res;
	}

	/**
	 * 通过id取得一行
	 * @param int $id
	 * @param string $tbName
	 * @param array|string $fields
	 * @return array
	 */
	function getRowById($id,$tbName,$fields=array())
	{
		$sql = 'SELECT ';
		if(is_array($fields)){
			$selectFields = $this->assembleFields($fields);
			$sql .= $selectFields;
		}else if(empty($fields) || '*' == $fields){
			$sql .= ' * ';
		}else{
			$sql .= ' `'.$fields.'` ';
		}
		$sql .= 'FROM `'.$this->tbPrefix.$tbName.'` WHERE `id`='.$id;
		$res = $this->execute_dql($sql);
		return $res[0];
	}
	/**
	 * 插入操作
	 * @param string $tbName
	 * @param array $params 要插入的值 'field'=>'value'
	 * @return int id 返回成功插入后的insert_id
	 */
	function insertOne($tbName,$params){
		if(!is_array($params)){
			die("wrong argument!");
		}
		$sql = "INSERT INTO `%s` SET %s";
		$sql_values = $this->assembleParams($params);
		
		$sql = sprintf($sql,$this->tbPrefix.$tbName,$sql_values);
		$this->execute_dml($sql);
		return $this->conn->insert_id;
	}

	public function getList($tbName,$fields = array(),$where='1=1',$start=10,$offset=0){
		$sql = "SELECT %s FROM %s WHERE %s %s ";
		$sqlFields = $this->assembleFields($fields);
		$sqlTable = $this->tbPrefix.$tbName;
		if(0 != intval($offset)){
			$sqlExtra = ' LIMIT '.intval($start).','.intval($offset);
		}else if(0 != intval($start) && 0 == intval($offset)){
			$sqlExtra = ' LIMIT '.intval($start);
		}else{
			$sqlExtra = '';
		}
		$sql = sprintf($sql,$sqlFields,$sqlTable,$where,$sqlExtra);
		$res = $this->execute_dql($sql);
		return $res;
	}

	public function assembleFields($fields){
		if(empty($fields)){
			return ' * ';
		}
		$res = '';
		$flag = 1;
		foreach($fields as $f){
			if(1 != $flag++){
				$res .= ',';
			}
			$res .= " `{$f}` ";
		}
		return $res;
	}
	
	public function assembleParams($params){
		$resTpl = " `%s`='%s' ";
		$res = '';
		$flag = 1;
		foreach ($params as $key => $value) {
			if(1 != $flag++){
				$res .= ',';
			}
			$res .= sprintf($resTpl,$key,$this->conn->real_escape_string($value));
		}
		return $res;
	}

	public function assembleSelectSQL($tbName,$fields=array(),$extra=''){
		$sql = 'SELECT %s FROM `%s` %s';
		$fields = $this->assembleFields($fields);
		$sql = sprintf($sql,$fields,$this->tbPrefix.$tbName,$extra);
		return $sql;
	}
	
	public function assembleUpdateSQL($tbName,$params,$extra){
		$sql = 'UPDATE `%s` SET %s %s';
		$setParams = $this->assembleParams($params);		
		$sql = sprintf($sql,$this->tbPrefix.$tbName,$setParams,$extra);
		return $sql;
	}
	
	public function assembleConditions($params,$condition='AND'){
        $res = '';
        $flag = 1;
        foreach($params as $v){
            if(1 != $flag++)$res .= " {$condition} ";
                $res .= " `{$v['field']}` {$v['sign']} '{$v['value']}'";
            }
        return $res;
    }
	
	public function assembleCountSQL($tbName,$params=array(),$condition='AND'){
        $sql = 'SELECT COUNT(`id`) AS count FROM `%s` %s';
        if(empty($condition)){
            $sql = sprintf($sql,$this->tbPrefix.$tbName,'');
        }else{
            $res = ' WHERE ';
            $res .= $this->assembleConditions($params,$condition);
            $sql = sprintf($sql,$this->tbPrefix.$tbName,$res);
        }
        return $sql;
    }

	public function close(){
		$this->conn->close();
    }

	public function __destruct(){
		$this->conn->close();
	}
}

