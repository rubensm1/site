<?php

abstract class Model implements JsonSerializable {

    protected static $conexao;
    protected static $useTable = FALSE;
    protected static $estrutura = array("id" => "int");
    protected $id;

    function Model($id = 0) {
		$class = get_class($this);
		if ($class::$useTable || $class == "Model") {
			self::init();
			//$this->id = self::lastID();
		}
		$this->id = $id;
    }
	
	public static function init() {
		if (self::$conexao == NULL) 
			self::$conexao = new Connexao();
	}

    public function getId() {
		return $this->id;
    }

    public function setId($id) {
		$this->id = $id;
    }

    /**
     * Seleciona o ultimo ID da tabela
     */
    public static function lastID() {
		$class = get_called_class();
		//if ($class::$useTable == FALSE)
		//	return 0;
		$sql = 'SELECT MAX(id) as id FROM ' . $class::$useTable . ';';
		return ((int) self::$conexao->executeQuery($sql)['id']);
    }

    public static function load($id = 1) {
		$class = get_called_class();
		$sql = 'SELECT * FROM ' . $class::$useTable . ' WHERE id = :id';
		$result = self::$conexao->executeQuery($sql, array("id" => $id));
		if ($result)
			return new $class($result);
		else
			return NULL;
    }

    public static function select($campos = NULL, $valores = NULL, $orderBy = NULL, $groupBy = NULL, $limit = NULL) {
		$sql = self::geraSelect($campos, $valores);
		if ($orderBy)
			$sql .= " ORDER BY $orderBy";
		if ($groupBy)
			$sql .= " GROUP BY $groupBy";
		if ($limit)
			$sql .= " LIMIT $limit";
		//throw new Exception($sql);
		if(is_array($valores)) {
			$keys = array_keys($valores);
			foreach ($keys as $key)
				if ($valores[$key] === NULL)
					unset($valores[$key]);
		}
		return self::$conexao->executeQueryAll($sql, $valores);
    }

	public static function loadList($valores = NULL, $orderBy = NULL, $groupBy = NULL, $limit = NULL) {
		$class = get_called_class();
		$generics = $class::select(NULL, $valores, $orderBy, $groupBy, $limit);
		$models = array();
		foreach ($generics as $generic)
			if ( isset($generic['id']) )
				$models[(int) $generic['id']] = new $class($generic);
			else
				$models[] = new $class($generic);
		return $models;
	}
	
    public function persist() {
		$class = get_class($this);
		if ($class::$useTable == FALSE)
			return FALSE;
		$obj = $class::load($this->id);
		$valores = $this->toArray();
		self::prepareToPersistInRelationalDatabase($valores);
		if ($obj)
			$sql = self::geraUpdate($valores);
		else {
			unset($valores['id']);
			$sql = self::geraInsert($valores);
		}
		//return $sql;
		self::$conexao->query($sql);
		self::$conexao->execute($valores);
		//self::$conexao->executeQuery($sql, $valores);
		if (!$obj)
			$obj = $class::load($class::lastId());
		return $obj;
    }

    public function delete() {
		$class = get_class($this);
		if ($class::$useTable == FALSE)
			return FALSE;
		if ($class::load($this->id))
			$sql = self::geraDelete(array('id' => $this->id));
		else
			return FALSE;
		self::$conexao->query($sql);
		self::$conexao->execute(array('id' => $this->id));
		return TRUE;
    }

    /**
     * Seleciona todos os campos da tabela referente ao modelo.
     * @return Array resultado da consulta, com objetos da classe
     */
    public static function all($limit = 0) {
	    $class = get_called_class();
	    $sql = 'SELECT * FROM ' . $class::$useTable . ' ORDER BY id' . ($limit ? " LIMIT $limit" : "");
	    $consulta = self::$conexao->executeQueryAll($sql);
	    //return $consulta;
	    $lista = array();
	    foreach ($consulta as $result)
	        $lista[$result['id']] = new $class($result);
	    return $lista;
    }
    
    /**
     * Seleciona todos os campos da tabela referente ao modelo.
     * @return Array resultado da consulta, com arrays
     */
    public static function allMap($limit = 0) {
	    $class = get_called_class();
	    $sql = 'SELECT * FROM ' . $class::$useTable . ' ORDER BY id' . ($limit ? " LIMIT $limit" : "");
	    $consulta = self::$conexao->executeQueryObjectAll($sql);
	    //return $consulta;
	    $lista = array();
	    foreach ($consulta as $result)
	        $lista[$result->id] = $result;
	    return $lista;
    }
	
	/**
     * Seleciona todos os ids da tabela referente ao modelo.
     * @return Array resultado da consulta
     */
	public static function allIds() {
		$class = get_called_class();
	    $sql = 'SELECT id FROM ' . $class::$useTable . ' ORDER BY id';
	    $consulta = self::$conexao->executeQueryAll($sql);
	    //return $consulta;
	    $lista = array();
	    foreach ($consulta as $result)
	        $lista[] = $result['id'];
	    return $lista;
	}
    
	public static function nativeQuery($sql) {
		return self::$conexao->executeQueryObjectAll($sql);
	}
	
    private function geraColunasQuery() {
		$class = get_class($this);
		$colunasQuery = "";
		foreach ($class::$estrutura as $coluna => $tipo)
			$colunasQuery .= "," . $coluna;
		return substr($colunasQuery, 1);
    }

    public static function consultaNomeColunas() {
	$class = get_called_class();
	$table = $class::$useTable;
	$colunsNames = array();
	$resultado = self::$conexao->getColunasTable($table);
	/* while ($linha = mysqli_fetch_array($resultado,MYSQLI_ASSOC)) {
	  array_push($colunsNames, $linha['Field']);
	  if ($linha['Key'] == 'PRI')
	  array_push($primaryKey, "<KEY>".$linha['Field']."</KEY>");
	  }
	  return $colunsNames; */
	return $resultado;
    }

    /**
     * Gera Query SQL Select<br/>
     * @param Array\String $campos Array ou String 
     * com os campos que serão selecionados na consulta.<br/>
     * Se $campos for Nulo todos os campos são selecionados. <br/>
     */
    private static function geraSelect($campos = null, $where = array()) {
		$class = get_called_class();
		$sql = 'SELECT ';
		if (is_array($campos)) {
			$campos = implode(",", $campos);
			//$campos = $this->arrayToString($campos); /*gera string com os campos*/
		}
		if ($campos != null) {
			$sql .= $campos; /* caso tenha algum campo insere no sql */
		} else {
			$sql .= '*'; /* Caso não tenha um campo especificado selecionara todos */
		}
		$sql .= ' FROM ' . $class::$useTable;
		if (!empty($where)) {
			$sql .= ' WHERE ';
			$i = FALSE;
			foreach ($where as $key => $value) { /* cria os parametros */
			if ($value === NULL)
				continue;
			if ($i)
				$sql .= ' AND ';
			else
				$i = TRUE;
			$sql .= $key . ' = :' . $key;
			}
		}
		return $sql;
    }

    /**
     * Gera o WHERE dos <b>SQL's</b> <br/>
     * Utiliza os campos desfinidos no atributo <b>data</b><br/>
     */
    private static function geraWhere($data) {
	$sql = '';
	if (!empty($data)) {
	    $sql .= ' WHERE ';
	    $i = 0;
	    foreach ($data as $key => $value) { /* cria parametros do sql */
		$num = count($data);
		$sql .= $key . ' = :' . $key;
		if ($i < $num - 1)
		    $sql .= ' AND ';
		$i++;
	    }
	}
	return $sql;
    }

    /**
     * Gera Query SQL Insert
     */
    private static function geraInsert($data) {
		$class = get_called_class();
		$num = count($data);
		$i = 0;
		$valores = $num == 1 ? 'VALUE (' : 'VALUES (';
		$sql = 'INSERT INTO ' . $class::$useTable . ' ( ';
		foreach ($data as $key => $value) {/* cria os parametros */
			$sql .= $key;
			if ($i < $num - 1)
			$sql .= ', ';
			$valores .= ':' . $key;
			if ($i < $num - 1)
			$valores .= ', ';
			$i++;
		}
		$valores .= ' );';
		$sql .= ' ) ' . $valores;
		return $sql;
    }

    /**
     * Gera Query SQL UPDATE
     */
    private static function geraUpdate($data) {
		$class = get_called_class();
		unset($data['id']);
		$num = count($data);
		$i = 0;
		$sql = 'UPDATE ' . $class::$useTable . ' SET ';
		foreach ($data as $key => $value) {/* cria os parametros */
			$sql .= $key . ' = :' . $key;
			if ($i < $num - 1)
			$sql .= ', ';
			$i++;
		}
		$sql .= ' WHERE id = :id';
		return $sql;
    }

    /**
     * Gera Query SQL DELETE <br/>
     */
    private static function geraDelete($where) {
		$class = get_called_class();
		$sql = 'DELETE FROM ' . $class::$useTable;
		if (!empty($where)) {
			$sql .= self::geraWhere($where); /* Gera o where */
		}
		return $sql;
    }
	
	/**
     * Prepara os valores que são objetos para serem inseridos em um banco de dados Relacional <br/>
     */
	private static function prepareToPersistInRelationalDatabase(&$valores){
		foreach ($valores as $key => $value) {
			if (is_object($value))
				$valores[$key] = $value->id;
		}
	}

    public static function htmlTable($limit = 0) {
		$class = get_called_class();
		$sql = 'SELECT * FROM ' . $class::$useTable . ' ORDER BY id' . ($limit ? " LIMIT $limit" : "");
		$consulta = self::$conexao->executeQueryAll($sql);
		$colunasBD = $class::consultaNomeColunas();
		$colunas = array();
		$html = '<table class="table table-bordered"><thead><tr>';
		foreach ($colunasBD as $key => $value) {
			$html .= "<th>" . ($value["Key"] == "PRI" ? "<u>" . $value["Field"] . "</u>" : $value["Field"]) . "</th>";
			$colunas[] = $value["Field"];
		}
		$html .= '</tr></thead><tbody>';
		foreach ($consulta as $result) {
			$html .= "<tr>";
			foreach ($colunas as $header)
			$html .= "<td>" . $result[$header] . "</td>";
			$html .= "</tr>";
		}
		return $html . "</tbody></table>";
    }

    public function jsonSerialize() {
		return (object) $this->toArray();
    }

    public function toArray() {
		return get_object_vars($this);
    }

}

?>
