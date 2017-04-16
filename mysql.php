<?php 
namespace CAI_mysql {
	use PDO,PDOException;
	class Manager {

		private $pdo;
		private function __construct() {}

		public static function create($attr,$database,$user,$psw) {
			$instance = new Manager;
			$instance->connect($attr,$database,$user,$psw);
			return $instance;
		}

		public function connect($attr,$database,$user,$psw) {
			$this->pdo = null;
			try {
				$this->pdo = new PDO("mysql:host=$attr;dbname=$database",$user,$psw);
				$this->pdo -> setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
			}catch (PDOException $e) 
				{ throw new DatabaseCreateException("数据库连接<br>error:<p>{$e->getMessage()}</p>");}
		}
		public function createDatabase($name) {
			try {
				$query = "create database $name";
				$this->pdo->exec($query);
			}catch(PDOException $e) 
				{ $this->throwQueryException("创建数据库",$query,$e); }
		}
		public function deleteDatabase($name) {
			try {
				$query = "drop database $name";
				$this->pdo->exec($query);
			}catch (PDOException $e) {$this->throwQueryException("删除数据库",$query,$e);}
		}
		public function createTable($tableName,$param) {
			if (!is_array($param))
				throw new QueryException("创建表:参数\"$param\"不是一个数组");

			try {
				$query = "create table $tableName(";
				foreach ($param as $str)
					$query = $query . $str . ",";
				$query =  truncate($query,1);
				$query = $query . ")";
				$this->pdo->exec($query);
			} catch (PDOException $e) {$this->throwQueryException("创建表",$query,$e);}
		}
		public function deleteTable($tableName) {
			try {
				$query = "drop table $tableName";
				$this->pdo->exec($query);
			}catch (PDOException $e) {$this->throwQueryException("删除表",$query,$e);}
		}
		public function insertRow($tableName,$columns,$values) {
			if (!is_array($columns) || !is_array($values) 
				|| count($columns) != count($values)) {
				throw new QueryException("插入行,参数错误<br>columns:<p>".output($columns)."</p>values:<p>".output($values)."</p>");
			}

			try {
				$query = "insert into $tableName(";
				foreach ($columns as $col)
					$query = $query . $col . ",";
				$query = truncate($query,1);
				$query = $query . ")values(";
				foreach ($values as $val)
					$query = $query . "'" . $val . "'" . ",";
				$query = truncate($query,1);
				$query = $query . ")";
				$this->pdo->exec($query);	
			} catch (PDOException $e) {$this->throwQueryException("插入行",$query,$e);}
		}
		public function insertRows($rows) {
			if (!$this->isRows($rows)) {
				$all = "";
				foreach ($rows as $row)
					$all = $all . output($row) . "<br>";
				throw new QueryException("插入多行,参数错误<p>$all</p>");	
			}
			try {
				$this->pdo->beginTransaction();
				foreach ($rows as $row) {
					$tableName = $row[0];
					$columns = $row[1];
					$values = $row[2];
					$this->insertRow($tableName,$columns,$values);
				}
				$this->pdo->commit();
			} catch (PDOException $e) { $this->throwQueryException("插入多行",$query,$e);}
		}
		public function deleteRow($tableName,$condition) {
			try {
				$query = "delete from $tableName";
				if ($condition != null) {
					$query = $query . " where $condition";
				}
				$this->pdo->exec($query);
			}catch(PDOException $e) { $this->throwQueryException("删除行",$query,$e);}
		}
		public function createPreHnadleInsertionSQL($tableName,$columns) {
			if (!is_array($columns) || !is_string($tableName)) 
				throw new QueryException("创建预处理SQL,参数错误.<p>".output($columns)."</p>");
			try {
				$query = "insert into $tableName(";
				foreach ($columns as $column)
					$query = $query . $column . ",";
				$query = truncate($query,1);
				$query = $query . ")values(";
				foreach ($columns as $column)
					$query = $query . ":" . $column . ",";
				$query = truncate($query,1);
				$query = $query . ")";
				$stmt = $this->pdo->prepare($query);
				$preHandleSQL = new PreHandleSQL($stmt,$columns);
				return $preHandleSQL;
			} catch (PDOException $e) {$this->throwQueryException("创建预处理SQL",$query,$e);}
			return null;
		}
		public function select($tableName,$columns,$condition) {
			try {
				$query = "select ";
				if (is_string($columns)) $query = $query . $columns;
				else  { 
					foreach ($columns as $column)
						$query = $query . $column . ",";
					$query = truncate($query,1);
				}
				$query = $query . " from $tableName";
				if ($condition != null) $query = $query . " where $condition";
				$stmt = $this->pdo->prepare($query);
				$stmt->execute();
				$stmt->setFetchMode(PDO::FETCH_ASSOC);
				$ft = $stmt->fetchAll();
				return $ft;
			} catch (PDOException $e) { $this->throwQueryException("查询",$query,$e); }
		}
		public function update($tableName,$condition,$keyValues) {
			try {
				$query = "update $tableName set ";
				foreach ($keyValues as $column => $value)
					$query = $query . $column . "=" . "'" . $value . "'" . ",";
				$query = truncate($query,1);
				$query = $query . " where " . $condition;
				echo "$query";
				$this->pdo->exec($query);
			} catch (PDOException $e) { $this->throwQueryException("更新行",$query,$e); }
		}

		private function throwQueryException($title,$sql,$error) {
			throw new QueryException("$title"."<br>SQL:<p>$sql</p> error:<p>{$error->getMessage()}</p>");
		}
		private function isRows($rows) {
			if (!is_array($rows)) return false;
			foreach ($rows as $row) {
				if (!is_string($row[0]) || !is_array($row[1]) || !is_array($row[2]))
					return false;
			}
			return true;
		}

	}

	class PreHandleSQL {

		var $stmt;
		var $params;
		var $columns;
		public function __construct($stmt,$columns) {
			$this->stmt = $stmt;
			$this->params = array();
			$this->columns = $columns;
			foreach ($this->columns as $column)
				$this->stmt->bindParam(":".$column,$this->params[$column]);
		}
		public function execute($values) {
			if (!is_array($values) || 
				count($values) != count($this->columns)) {
				throw new QueryException("预处理语句,参数错误.<br>columns:<p>".output($this->columns)."</p>参数:<p>".output($values)."</p>");	
			}
			try {
				$index = 0;
				foreach ($this->columns as $column) {
					$this->params[$column] = $values[$index];
					$index++;
				}
				$this->stmt->execute();
			} catch (PDOException $e) { $this->throwQueryException("预处理语句",$query,$e); }
		}
	}

	/*
	 *	Exception
	 */
	class QueryException extends \Exception {
		public function errorMessage() {
			return "<p>CAIMysql::QueryException ==> ".$this->getMessage()."</p>";
		}
	}
	class DatabaseCreateException extends \Exception {
		public function errorMessage() {
			return "<p>CAIMysql::DatabaseCreateException ==> ".$this->getMessage()."</p>";
		}
	}

	/*
	 *    Helper
	 */
	function truncate($str,$num) {
			return substr($str, 0,strlen($str) - $num);
	}
	function output($var) {
		if (is_array($var)) {
			$str = "";
			foreach ($var as $value)
				$str = $str.output($value).", ";
			$str = truncate($str,1);
			return $str;
		}else return $var;
	} 
}
 ?>