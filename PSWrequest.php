<?php
setlocale (LC_ALL, "pt_BR");

//ini_set('display_errors',1);
//ini_set('display_startup_erros',1);
error_reporting(E_ALL);

sfsdfsdf
echo("bunda");



class PSWrequest extends SQLite3 {

	/*-- erros: constantes --*/
	private $DB    = "construct: Undefined database!";
	private $TABLE = "\$table: Enter the table name accordingly!";
	private $DATA  = "\$data: The input value must be an array with content!";
	private $WHERE = "\$where: The input value must be an array with content!";
	private $BOOL  = "\$bool: The input value must be boolean!";

	/*-- erros: verificação --*/
	private function checkError($DB = NULL, $table = NULL, $data = NULL, $wherer = NULL, $bool = NULL) {
		if ($DB    !== NULL && (!is_file($DB) && $DB !== ":memory:")) {
			throw new Exception($CONSTRUCTOR);
		}
		if ($table !== NULL && (gettype($table) !== "string" || strlen(trim($table)) == 0)) {
			throw new Exception($TABLE);
		}
		if ($data  !== NULL && (gettype($data) !== "array" || count($data) == 0)) {
			throw new Exception($DATA);
		}
		if ($where !== NULL && (gettype($where) !== "array" || count($where) == 0)) {
			throw new Exception($WHERE);
		}
		if ($bool !== NULL && (gettype($where) !== "boolean") {
			throw new Exception($BOOL);
		}
		return 0;
	}

	/*-- Construtor: define o banco de dados --*/
	public function __construct($DB = NULL) {
		$this->checkError($DB);
		$this->open($DB);
		return;
	}

	/*-- output: imprime o retorno da requisição --*/
	private function output($query, $exec) {
		$data = [];
		if (!$query || $this->lastErrorCode() !== 0) {
			$data["error"]   = $this->lastErrorCode() !== 0 ? True : False;
			$data["message"] = $this->lastErrorMsg();
			$data["id"]      = $this->lastInsertRowID();
		} else {
			while ($i = $exec->fetchArray()) {
				array_push($data, $i);
			}
		}
		echo json_encode($data);
		return 0;
	}

	/*-- sql: define o que fazer a partir de um comando sql --*/
	public function sql($input) {
		$sql   = trim($input);
		$query = preg_match('/^SELECT/i', $sql) ? True : False;
		$exec  = $query ? $this->query($sql) : $this->exec($sql);
		$this->output($query, $exec);
		return 0;
	}
	
	/*-- insert: insere dados na tabela a partir de um array --*/
	public function insert($table, $data) {
		$this->checkError(NULL, $table, $data);
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
		$this->checkError(NULL, $table, $data, $where, $and);
		$set = [];
		$whr = [];
		foreach($data as $key => $value) {
			array_push($set, "{$key} = '{$value}'");
		}
		foreach($where as $key => $value) {
			array_push($whr, "{$key} = '{$value}'");
		}
		$set = join(", ", $set);
		$whr = join(($and == True ? " AND " : " OR "), $whr);
		$sql = "UPDATE {$table} SET {$set} WHERE {$whr};";
		$this->sql($sql);
		return 0;
	}

	/*-- delete: exclui dados da tabela a partir de um array --*/
	public function update($table, $where, $and = True) {
		$this->checkError(NULL, $table, NULL, $where, $and);
		$whr = [];
		foreach($where as $key => $value) {
			array_push($whr, "{$key} = '{$value}'");
		}
		$whr = join(($and == True ? " AND " : " OR "), $whr);
		$sql = "DELETE FROM {$table} WHERE {$whr};";
		$this->sql($sql);
		return 0;
	}

	/*-- view: returna todos os itens da pesquisa --*/
	public function view($table, $where, $and = True) {
		$this->checkError(NULL, $table, NULL, NULL, $and);
		if (gettype($where) == "array" && count($where) > 0) {
			$whr = [];
			foreach($where as $key => $value) {
				array_push($whr, "{$key} = '{$value}'");
			}
			$whr = join(($and == True ? " AND " : " OR "), $whr);
			$whr = " WHERE {$whr}"
		} else {
			$whr = "";
		}
		$sql = "SELECT * FROM {$table}{$whr};";
		$this->sql($sql);
		return 0;
	}

};
?>
