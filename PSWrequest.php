<?php
/*-- configuração --*/
//phpinfo();
setlocale (LC_ALL, "pt_BR");
ini_set('display_errors', False);

class PSWrequest extends SQLite3 {

	/*------------------------------ PRIVADO ---------------------------------*/

	private $VERSION = "v1.0.0";

	/*-- Atributos para guardar informação sobre a requisição --*/
	private $ERROR;
	private $TYPE;
	private $MESSAGE;
	private $DATA;
	private $COMMAND = "";

	/*-- Método para definir os atributos da requisição --*/
	private function setRequest($error = False, $type = "php", $message = "", $data = NULL) {
		$this->ERROR   = $error;
		$this->TYPE    = $type;
		$this->MESSAGE = $message;
		$this->DATA    = $data;
		return True;
	}

	/*------------------------------ PÚBLICO ---------------------------------*/

	/*-- Método mágico que retornará a versão da classe --*/
	public function __toString() {
		return $this->COMMAND;
	}
	
	/*-- Método mágico para var_dump --*/
	public function __debugInfo() {
		return Array(
			"COMMAND" => $this->COMMAND,
			"VERSION" => $this->VERSION
		);
	}

	/*-- Método que imprime em JSON o resultado da última requisição --*/
	public function getResponse() {
		$request = Array(
			"error"   => $this->ERROR,
			"message" => $this->TYPE === "php" ? "There was an error communicating with the database." : $this->MESSAGE,
			"data"    => $this->DATA
		);
		echo json_encode($request);
		$this->close();
		$value = $this->ERROR ? 1 : 0;
		exit($value);
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
	public function getData() {
		return $this->DATA;
	}

	/*-- Método que imprime em JSON a informação de SQL da última requisição --*/
	public function getQuery() {
		echo json_encode($this->DATA);
		$this->close();
		$value = $this->ERROR ? 1 : 0;
		exit($value);
	}

	/*-- Método construtor que define o banco de dados --*/
	public function __construct($db = ":memory:") {
		$this->setRequest();
		try {
			$this->open($db);
		} catch (Exception $e) {
			$this->setRequest(True, "php", $e->getMessage());
			$this->getResponse();
		}
		return;
	}

	/*-- Método que define o que fazer a partir de um comando sql --*/
	public function sql($input) {
		$this->COMMAND = $input;
		$this->setRequest();
		try {
			if (gettype($input) !== "string") {
				throw new Exception("PSWrequest::sql - Invalid \"input\" argument.");
			}
			$input   = trim($input);
			$query   = preg_match('/^SELECT/i', $input) ? True : False;
			$exec    = $query ? $this->query($input) : $this->exec($input);
			$error   = $this->lastErrorCode() !== 0 ? True :  False;
			$message = $this->lastErrorMsg();
			if (!$query) {
				$data = !$error && preg_match('/^INSERT/i', $input) ? $this->lastInsertRowID() : NULL;
			} else {
				$data = Array();
				while ($array = $exec->fetchArray()) {
					$item = Array();
					foreach ($array as $key => $value) {
						$item[$key] = $value;
					}
					array_push($data, $item);
				}
			}
			$this->setRequest($error, "sql", $message, $data);
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
				throw new Exception("PSWrequest::insert - Invalid \"table\" argument.");
			}
			if (gettype($data) !== "array" || count($data) === 0) {
				throw new Exception("PSWrequest::insert - Invalid \"data\" argument.");
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
	public function update($table = NULL, $data = NULL, $where = NULL) {
		$this->setRequest();
		try {
			if (gettype($table) !== "string" || strlen(trim($table)) === 0) {
				throw new Exception("PSWrequest::update - Invalid \"table\" argument.");
			}
			if (gettype($data) !== "array" || count($data) === 0) {
				throw new Exception("PSWrequest::update - Invalid \"data\" argument.");
			}
			if (gettype($where) !== "string" || !array_key_exists(trim($where), $data)) {
				throw new Exception("PSWrequest::update - Invalid \"where\" argument.");
			}
			$table = trim($table);
			$where = trim($where);
			$whr   = "{$where} = '{$data[$where]}'";
			$set   = [];
			unset($data[$where]);
			foreach($data as $key => $value) {
				array_push($set, "{$key} = '{$value}'");
			}
			$set = join(", ", $set);
			$sql = "UPDATE {$table} SET {$set} WHERE {$whr};";
			return $this->sql($sql);
		} catch (Exception $e) {
			$this->setRequest(True, "php", $e->getMessage());
			return False;
		}
	}

	/*-- Método que exclui dados da tabela a partir de um array --*/
	public function delete($table = NULL, $data = NULL, $where = NULL) {
		$this->setRequest();
		try {
			if (gettype($table) !== "string" || strlen(trim($table)) === 0) {
				throw new Exception("PSWrequest::delete - Invalid \"table\" argument.");
			}
			if (gettype($data) !== "array" || count($data) === 0) {
				throw new Exception("PSWrequest::delete - Invalid \"data\" argument.");
			}
			if (gettype($where) !== "string" || !array_key_exists(trim($where), $data)) {
				throw new Exception("PSWrequest::delete - Invalid \"where\" argument.");
			}
			$table = trim($table);
			$where = trim($where);
			$whr   = "{$where} = '{$data[$where]}'";
			$sql = "DELETE FROM {$table} WHERE {$whr};";
			return $this->sql($sql);
		} catch (Exception $e) {
			$this->setRequest(True, "php", $e->getMessage());
			return False;
		}
	}

	/*-- Método que returna todos os itens da pesquisa --*/
	public function view($table, $data = Array(), $where = NULL) {
		$this->setRequest();
		try {
			if (gettype($table) !== "string" || strlen(trim($table)) === 0) {
				throw new Exception("PSWrequest::view - Invalid \"table\" argument.");
			}
			if (gettype($data) !== "array") {
				throw new Exception("PSWrequest::view - Invalid \"data\" argument.");
			}
			if ($where !== NULL && !array_key_exists(trim($where), $data)) {
				throw new Exception("PSWrequest::view - Invalid \"where\" argument.");
			}
			$table = trim($table);
			$where = gettype($where) === "string" ? trim($where) : $where;
			if (count($data) === 0 || $where === NULL) {
				$sql = "SELECT * FROM {$table};";
			} else {
				$whr   = "{$where} = '{$data[$where]}'";
				$sql = "SELECT * FROM {$table} WHERE {$whr};";
			}
			return $this->sql($sql);
		} catch (Exception $e) {
			$this->setRequest(True, "php", $e->getMessage());
			return False;
		}
	}
}
?>
