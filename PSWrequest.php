<?php
setlocale (LC_ALL, "pt_BR");

class DB extends SQLite3 {

	public function __construct($db = NULL) {
		/*Instanciar o objeto: arquivo obrigatório*/
		if (!is_file($db) && $db !== ":memory:") {
			throw new Exception("DB: wrong argument");
		}
		if (is_file($db)) {
			//chmod(dirname($db), 0777);
		}
		$this->open($db);
		return;
	}

	public function go($sql) {
		if (strlen(trim($sql)) === 0) {
			throw new Exception("DB->go: wrong argument");
		}
		
		$query = preg_match('/^SELECT/i', trim($sql)) ? True : False;
		$exec  = $query ? $this->query($sql) : $this->exec($sql);

		if (!$query || $this->lastErrorCode() !== 0) {
			$data = [
				"error" => $this->lastErrorCode() !== 0 ? True : False,
				"msg"   => $this->lastErrorMsg(),
				"id"    => $this->lastInsertRowID()
			];
		} else {
			$data = [];
			while ($i = $exec->fetchArray()) {
				array_push($data, $i);
			}
		}

		echo json_encode($data);

		return;
	}
	
	public function view($table, $where = NULL) {
		$this->go("SELECT * FROM ".$table.($where === NULL ? ";" : " WHERE ".$where.";"));
		return;
	}
	
	public function insert($table, $array) {
		if (count($array) === 0) {
			throw new Exception("DB->insert: Empty array");
		}
		$values  = "";
		$columns = "";
		foreach($array as $key => $value) {
			$values  .= $values  === "" ? "'".$value."'" : ", '".$value."'";
			$columns .= $columns === "" ? $key : ", ".$key;
		}
		$this->go("INSERT INTO ".$table." (".$columns.") VALUES (".$values.");");
		return;
	}
	
	public function vaccum() {
		$this->go("VACUUM;");
		return;
	}

};

?>
