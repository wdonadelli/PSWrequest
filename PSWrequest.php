<?php
/*-- configuração --*/
//phpinfo();
setlocale (LC_ALL, "pt_BR");
ini_set('display_errors', True);

class PSWrequest extends SQLite3 {

	/*------------------------------ PRIVADO ---------------------------------*/

	/*-- Método privado para obter o argumento WHERE --*/
	private function getWhere($where, $and = True) {
		$whr = [];
		foreach($where as $key => $value) {
			array_push($whr, "{$key} = '{$value}'");
		}
		$whr = join(($and == True ? " AND " : " OR "), $whr);
		return $whr;
	}

	/*-- Atributos privados para guardar informação sobre a requisição --*/
	private $ERROR;
	private $TYPE;
	private $MESSAGE;
	private $SQL;

	/*-- Método privado para definir os atributos da requisição --*/
	private function setRequest($error = False, $type = "", $message = "", $sql = NULL) {
		$this->ERROR   = $error;
		$this->TYPE    = $type;
		$this->MESSAGE = $message;
		$this->SQL     = $sql;
		return True;
	}

	/*------------------------------ PÚBLICO ---------------------------------*/

	/*-- Método que imprime em JSON o resultado da última requisição --*/
	public function getRequest() {
		$request = Array(
			"error"   => $this->ERROR,
			"type"    => $this->TYPE,
			"message" => $this->MESSAGE,
			"sql"     => $this->SQL
		);
		echo json_encode($request);
		exit(0);
	}

	/*-- Método que retorna se houve erro na última requisição --*/
	public function getError() {
		return $this->ERROR;
	}

	/*-- Método que retorna o tipo da última requisição --*/
	public function getType() {
		return $this->TYPE;
	}

	/*-- Método que retorna a mensagem da última requisição --*/
	public function getMessage() {
		return $this->MESSAGE;
	}
	
	/*-- Método que retorna a informação de SQL da última requisição --*/
	public function getSQL() {
		return $this->SQL;
	}

	/*-- Método que imprime em JSON a informação de SQL da última requisição --*/
	public function getQuery() {
		echo json_encode($this->SQL);
		exit(0);
	}

	/*-- Método construtor que define o banco de dados --*/
	public function __construct($db = ":memory:") {
		$this->setRequest();
		try {
			$this->open($db);
		} catch (Exception $e) {
			$this->setRequest(True, "php", $e->getMessage());
			$this->showRequest();
			exit(1);
		}
		return;
	}

	/*-- Método que define o que fazer a partir de um comando sql --*/
	public function sql($input) {
		/*echo $input."<br>";*/
		$this->setRequest();
		try {
			if (gettype($input) !== "string") {
				throw new Exception("PSWrequest::sql - Invalid argument.");
			}
			$input   = trim($input);
			$query = preg_match('/^SELECT/i', $input) ? True : False;
			$exec  = $query ? $this->query($input) : $this->exec($input);
			if (!$query || $this->lastErrorCode() !== 0) {
				$error   = $this->lastErrorCode() !== 0 ? True : False;
				$message = $this->lastErrorMsg();
				$sql     = NULL;
				if (!$error && preg_match('/^INSERT/i', $input)) {
					$sql = Array("lastID" => $this->lastInsertRowID());
				}
				$this->setRequest($error, "sql", $message, $sql);
			} else {
				$sql = Array();
				while ($array = $exec->fetchArray()) {
					$item = Array();
					foreach ($array as $key => $value) {
						$item[$key] = $value;
					}
					array_push($sql, $item);
				}
				$this->setRequest(False, "sql", "", $sql);
			}
		} catch (Exception $e) {
			$this->setRequest(True, "php", $e->getMessage());
			return False;
		}
		return True;
	}
	
	/*-- Método que insere dados na tabela a partir de um array --*/
	public function insert($table = NULL, $data = NULL) {
		$this->setRequest();
		try {
			if (gettype($table) !== "string" || strlen(trim($table)) === 0) {
				throw new Exception("PSWrequest::insert - Invalid table argument.");
			}
			if (gettype($data) !== "array" || count($data) === 0) {
				throw new Exception("PSWrequest::insert - Invalid data argument.");
			}
			$table = trim($table);
			$col = [];
			$val = [];
			foreach($data as $key => $value) {
				array_push($col, $key);
				array_push($val, "'{$value}'");
			}
			$col = join(", ", $col);
			$val = join(", ", $val);
			$sql = "INSERT INTO {$table} ({$col}) VALUES ({$val});";
			return $this->sql($sql);
		} catch (Exception $e) {
			$this->setRequest(True, "php", $e->getMessage());
			return False;
		}
	}

	/*-- Método que atualiza dados da tabela a partir de um array --*/
	public function update($table = NULL, $data = NULL, $where = NULL, $and = True) {
		$this->setRequest();
		try {
			if (gettype($table) !== "string" || strlen(trim($table)) === 0) {
				throw new Exception("PSWrequest::update - Invalid table argument.");
			}
			if (gettype($data) !== "array" || count($data) === 0) {
				throw new Exception("PSWrequest::update - Invalid data argument.");
			}
			if (gettype($where) !== "array" || count($data) === 0) {
				throw new Exception("PSWrequest::update - Invalid where argument.");
			}
			$table = trim($table);
			$set = [];
			foreach($data as $key => $value) {
				array_push($set, "{$key} = '{$value}'");
			}
			$set = join(", ", $set);
			$whr = $this->getWhere($where, $and);
			$sql = "UPDATE {$table} SET {$set} WHERE {$whr};";
			return $this->sql($sql);
		} catch (Exception $e) {
			$this->setRequest(True, "php", $e->getMessage());
			return False;
		}
	}

	/*-- Método que exclui dados da tabela a partir de um array --*/
	public function delete($table = NULL, $where = NULL, $and = True) {
		$this->setRequest();
		try {
			if (gettype($table) !== "string" || strlen(trim($table)) === 0) {
				throw new Exception("PSWrequest::delete - Invalid table argument.");
			}
			if (gettype($where) !== "array" || count($data) === 0) {
				throw new Exception("PSWrequest::delete - Invalid where argument.");
			}
			$whr = $this->getWhere($where, $and);
			$sql = "DELETE FROM {$table} WHERE {$whr};";
			return $this->sql($sql);
		} catch (Exception $e) {
			$this->setRequest(True, "php", $e->getMessage());
			return False;
		}
	}

	/*-- Método que returna todos os itens da pesquisa --*/
	public function view($table, $where = Array(), $and = True) {
		$this->setRequest();
		try {
			if (gettype($table) !== "string" || strlen(trim($table)) === 0) {
				throw new Exception("PSWrequest::view - Invalid table argument.");
			}
			if (gettype($where) !== "array") {
				throw new Exception("PSWrequest::view - Invalid where argument.");
			}
			if (count($where) === 0) {
				$sql = "SELECT * FROM {$table};";
			} else {
				$whr = $this->getWhere($where, $and);
				$sql = "SELECT * FROM {$table} WHERE {$whr};";
			}
			return $this->sql($sql);
		} catch (Exception $e) {
			$this->setRequest(True, "php", $e->getMessage());
			return False;
		}
	}
}
$a = new PSWrequest();
$a->sql("CREATE TABLE IF NOT EXISTS loko (col1 TEXT, col2 NUMBER)");
$a->sql("INSERT INTO loko (col1, col2) VALUES ('willian', 36)");
$a->sql("INSERT INTO loko (col1, col2) VALUES ('helen', 36)");
$a->insert("loko", Array("col1" => "helena", "col2" => 2));
//$a->sql("UPDATE loko SET col1 = 'asdasds' WHERE col1 = 'willian'");
//$a->sql("SELECT * FROM loko");
//$a->getQuery();
$a->view("loko", Array("col1" => "willian", "col1" => "helena"), False);
$a->getQuery();
?>
