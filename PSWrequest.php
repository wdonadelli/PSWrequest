<?php
/*-- configuração --*/
//phpinfo();
setlocale (LC_ALL, "pt_BR");
ini_set('display_errors', True);

class PSWrequest extends SQLite3 {

	/*-- PRIVADOS --*/
	
	/*-- resultado --*/
	private $DATA = "[]";

	/*-- erros --*/
	private $ERRORS = Array(
		0 => "No mistakes.",
		1 => "Database not informed.",
		2 => "Database not found.",
		3 => "Table not informed.",
		4 => "The argument \$data must be a nonempty array.",
		5 => "The argument \$where must be a nonempty array.",
		6 => "The argument \$input must be a nonempty string.",
	);

	/*-- exibindo erros --*/
	private function checkError($method, $code) {
		$error = Array(
			"error"   => $code === 0 ? False : True,
			"message" => "{$method} - {$this->ERRORS[$code]}",
			"id"      => 'PSWrequestError'
		);
		if ($code !== 0) {
			$this->DATA = json_encode($error);
		}
		if ($code !== 0) {
			$method = strtolower($method);
			throw new Exception("PSWrequestError - PSWrequest::{$method}");
		}
		return 0;
	}

	/*-- checando entradas --*/
	private function checkDataBase($value) {
		$error = 0;
		if (gettype($value) !== "string" || strlen(trim($value)) === 0) {
			$error = 1;
		} else if (!is_file(trim($value)) && $value !== ":memory:")  {
			$error = 2;
		}
		return $error;
	}

	private function checkTable($value) {
		$error = 0;
		if (gettype($value) !== "string" || strlen(trim($value)) === 0) {
			$error = 3;
		}
		return $error;
	}

	private function checkData($value) {
		$error = 0;
		if (gettype($value) !== "array" || count($value) === 0) {
			$error = 4;
		}
		return $error;
	}

	private function checkWhere($value) {
		$error = 0;
		if (gettype($value) !== "array" || count($value) === 0) {
			$error = 5;
		}
		return $error;
	}

	private function checkSQL($value) {
		$error = 0;
		if (gettype($value) !== "string" || strlen(trim($value)) === 0) {
			$error = 6;
		}
		return $error;
	}

	/*-- obtendo WHERE --*/
	private function getWhere($where, $and = True) {
		if ($this->checkWhere($where) === 0) {
			$whr = [];
			foreach($where as $key => $value) {
				array_push($whr, "{$key} = '{$value}'");
			}
			$whr = join(($and == True ? " AND " : " OR "), $whr);
		} else {
			$whr = "";
		}
		return $whr;
	}

	/*-- output: imprime o retorno da requisição --*/
	private function output($query, $exec) {
		$data = [];
		if (!$query || $this->lastErrorCode() !== 0) {
			$data["error"]   = $this->lastErrorCode() !== 0 ? True : False;
			$data["message"] = $this->lastErrorMsg();
			$data["id"]      = $this->lastInsertRowID();
		} else {
			while ($array = $exec->fetchArray()) {//print_r($i);
				$item = Array();
				foreach ($array as $key => $value) {
					if (gettype($key) === "string") {
						$item[$key] = $value;
					}
				}
				array_push($data, $item);
			}
		}
		$this->DATA = json_encode($data);
		return 0;
	}

	/*-- PÚBLICO --*/

	/*-- Construtor: define o banco de dados --*/
	public function __construct($db = ":memory:") {
		$this->checkError("CONSTRUCTOR", $this->checkDataBase($db));
		$this->open(trim($db));
		return;
	}

	/*-- sql: define o que fazer a partir de um comando sql --*/
	public function sql($input) {
		$this->checkError("SQL", $this->checkSQL($input));

		$sql   = trim($input);
		$query = preg_match('/^SELECT/i', $sql) ? True : False;
		$exec  = $query ? $this->query($sql) : $this->exec($sql);
		$this->output($query, $exec);
		return 0;
	}
	
	/*-- insert: insere dados na tabela a partir de um array --*/
	public function insert($table, $data) {
		$this->checkError("INSERT", $this->checkTable($table));
		$this->checkError("INSERT", $this->checkData($data));

		$col = [];
		$val = [];
		foreach($data as $key => $value) {
			array_push($col, $key);
			array_push($val, "'{$value}'");
		}
		$col = join(", ", $col);
		$val = join(", ", $val);
		$sql = "INSERT INTO {$table} ({$col}) VALUES ({$val});";
		$this->sql($sql);
		return 0;
	}

	/*-- update: atualiza dados da tabela a partir de um array --*/
	public function update($table, $data, $where, $and = True) {
		$this->checkError("UPDATE", $this->checkTable($table));
		$this->checkError("UPDATE", $this->checkData($data));
		$this->checkError("UPDATE", $this->checkWhere($where));

		$set = [];
		foreach($data as $key => $value) {
			array_push($set, "{$key} = '{$value}'");
		}
		$set = join(", ", $set);
		$whr = $this->getWhere($where, $and);
		$sql = "UPDATE {$table} SET {$set} WHERE {$whr};";
		$this->sql($sql);
		return 0;
	}

	/*-- delete: exclui dados da tabela a partir de um array --*/
	public function delete($table, $where, $and = True) {
		$this->checkError("DELETE", $this->checkTable($table));
		$this->checkError("DELETE", $this->checkWhere($where));

		$whr = $this->getWhere($where, $and);
		$sql = "DELETE FROM {$table} WHERE {$whr};";
		$this->sql($sql);
		return 0;
	}

	/*-- view: returna todos os itens da pesquisa --*/
	public function view($table, $where = Array(), $and = True) {
		$this->checkError("VIEW", $this->checkTable($table));
		
		$whr = $this->getWhere($where, $and);
		$sql = $whr !== "" ? "SELECT * FROM {$table} WHERE {$whr};" : "SELECT * FROM {$table};";
		$this->sql($sql);
		return 0;
	}
	
	/*--Imprime o resultado --*/
	public function show() {
		echo $this->DATA;
		return 0;
	}
}
$a = new PSWrequest('');
$a->sql("create table if not exists doida (a TEXT, b NUMBER);");
$a->insert("doida", $_GET);
$a->insert("doida", $_GET);
$a->view("doida");
$a->show();
?>
