<?php namespace Pauldro\Minicli\Database\MeekroDB;
// MeekroDB
use MeekroDB;
use MeekroDBException;
// Pauldro Minicli
use Pauldro\Minicli\Database\DatabaseConnector;
use Pauldro\Minicli\Database\Exceptions\ConnectionFailureException;
use Pauldro\Minicli\Util\SessionVars as Session;

/**
 * Template Class for database CRUD
 * 
 * @static static $instance
 * @property MeekroDB $db
 */
abstract class AbstractTable {
	const SESSION_CONNECTION_NAME = '';
	const TABLE = '';
	const FORMAT_DATETIME = 'Y-m-d H:i:s';
	const COLUMNS = [
		'id'		=> ['INT', 'NOT NULL', 'AUTO_INCREMENT'],
		'ponbr' 	=> ['VARCHAR(12)', 'DEFAULT NULL'],
		'updated'	=> ['DATETIME', 'DEFAULT NULL'],
		'userid'	=> ['VARCHAR(45)', 'DEFAULT NULL'],
	];
	const PRIMARYKEY = ['id'];
	const RECORDKEY  = ['id'];
	const RECORD_CLASS = 'Pauldro\\Minicli\\Database\\MeekroDB\\\Record';
	const KEY_GLUE	  = '|';
	const REGEX_TABLE_DOESNT_EXIST = "/(Table)\s\'\w+\.\w+\'\s(doesn't exist)/";

	protected static $instance;
	protected $db;


	/** @return static */
	public static function instance()
	{
		if (empty(static::$instance)) {
			$instance = new static();
			static::$instance = $instance;
			static::$instance->init();
		}
		return static::$instance;
	}

	/**
	 * Init Table, install if needed
	 * @return bool
	 */
	public function init() : bool
	{
		$this->initMeekroDB();
		return $this->initDbTable();
	}

	/**
	 * Return MeekroDB Connection
	 * @throws ConnectionFailureException
	 */
	public function initMeekroDB() : void
	{
		/** @var DatabaseConnector|null */
		$db = Session::getFor('databases', static::SESSION_CONNECTION_NAME);

		if (empty($db)) {
			throw new ConnectionFailureException("DB connection not found for " . static::SESSION_CONNECTION_NAME);
		}
		$meekrodb = $db->meekrodb();
		if (empty($meekrodb)) {
			throw new ConnectionFailureException("Meekro DB connection not found for " . static::SESSION_CONNECTION_NAME);
		}
		$this->db = $meekrodb;
	}

	/**
	 * Initialize Database Table
	 * @return bool
	 */
	public function initDbTable() : bool 
	{
		if ($this->tableExists()) {
			return true;
		}
		return $this->install();
	}

	/**
	 * Install Table in Database
	 * @return bool
	 */
	public function install() : bool
	{
		if ($this->tableExists()) {
			return true;
		}
		return $this->createTable();
	}

/* =============================================================
	Query Functions
============================================================= */
	/**
	 * Return Query for Creating Table in Database
	 * @return string
	 */
	public function queryCreateTable() : string
	{
		$sql = 'CREATE TABLE ' . static::TABLE;
		
		$cols = [];

		foreach (static::COLUMNS as $name => $attr) {
			$column = array_merge(["`$name`"], $attr);
			$cols[] = implode(' ', $column);
		}
		$sql .= '(' . PHP_EOL;
		$sql .= implode(", \n", $cols);
		$sql .= ',' . PHP_EOL;

		$keys = [];

		foreach (static::PRIMARYKEY as $key) {
			$keys[] = "`$key`";
		}

		$sql .= 'PRIMARY KEY (' . implode(",", $keys) . ')';
		$sql .= PHP_EOL . ");";
		return $sql;
	}

/* =============================================================
	Read Functions
============================================================= */
	/**
	 * Return if Table Exists
	 * @return bool
	 */
	public function tableExists() : bool
	{
		try {
			$count = $this->countAll();
		} catch (MeekroDBException $e) {
			return preg_match(self::REGEX_TABLE_DOESNT_EXIST, $e->getMessage()) ? false : true;
		}
		return true;
	}

	/**
	 * Return the total number of records
	 * @return int
	 */
	public function countAll() : int
	{
		$tbl = static::TABLE;
		return intval($this->db->queryFirstField("SELECT COUNT(*) FROM $tbl"));
	}

	/**
	 * Return all records
	 * @return RecordList{Record}
	 */
	public function fetchAll() : RecordList
	{
		$tbl = static::TABLE;
		$results = $this->db->query("SELECT * FROM $tbl");
		$list = new RecordList();

		if (empty($results)) {
			return $list;
		}

		foreach ($results as $result) {
			$record = $this->newRecord();
			$record->setArray($result);
			$list->add($record);
		}
		return $list;
	}

/* =============================================================
	Create, Update, Delete Functions
============================================================= */
	/**
	 * Return if Table can be Created
	 * @return bool
	 */
	public function createTable() : bool
	{
		$sql = $this->queryCreateTable();

		try {
			$this->db->query($sql);
		} catch (MeekroDBException $e) {
			return false;
		}
		return true;
	}

	/**
	 * Insert Record
	 * @param  Record $data
	 * @return bool
	 */
	public function insert(Record $data) : bool
	{
		if (array_key_exists('updated', static::COLUMNS)) {
			$data->set('updated', date(static::FORMAT_DATETIME));
		}
		return boolval($this->db->insert(static::TABLE, $data->data));
	}

	/**
	 * Update Record
	 * @param  Record $data
	 * @return bool
	 */
	public function update(Record $data) : bool
	{
		if (array_key_exists('updated', static::COLUMNS)) {
			$data->set('updated', date(static::FORMAT_DATETIME));
		}
		return boolval($this->db->update(static::TABLE, $data->data, $this->getRecordPrimaryKeyValuesArray($data)));
	}

	/**
	 * Return Record Data class
	 * @return Record
	 */
	public function newRecord() : Record
	{
		$class = static::RECORD_CLASS;
		return new $class();
	}

/* =============================================================
	Param, Key Functions
============================================================= */
	/**
	 * Return Parameters for Set
	 * @param  array $cols
	 * @return string		column=:column
	 */
	protected function getParamsForQuery($cols, $glue = ',') : string
	{
		$data = [];
		foreach ($cols as $col) {
			$data[] = "$col=:$col";
		}
		return implode($glue, $data);
	}

	/**
	 * Return Parameters Key Arrays
	 * @param  Record	 $data
	 * @return string		  :col1,:col2
	 */
	protected function getParamKeysArray(Record $data) : array
	{
		return	array_keys($data->data);
	}

	/**
	 * Return Parameters Key String
	 * @param  Record	 $data
	 * @return string		  :col1,:col2
	 */
	protected function getParamKeysString(Record $data) : string
	{
		return ':' . implode(',:', array_keys($data->data));
	}

	/**
	 * Return Parameters Keyed by Param Key
	 * @param  Record $data
	 * @param  array $keys	
	 * @return array		   [':key' => $value]
	 */
	protected function getParamKeyValues(Record $data, $keys = []) : array
	{
		$params = [];

		foreach ($data->data as $key => $value) {
			if (in_array($key, $keys) || empty($keys)) {
				$params[':' . $key] = $value;
			}
		}
		return $params;
	}

	/**
	 * Return Parameters Keyed by Param Key
	 * @param  array $data
	 * @param  array $keys	
	 * @return array		   [':key' => $value]
	 */
	protected function getParamKeyValuesArray(array $data, $keys = []) : array
	{
		$params = [];

		foreach ($data as $key => $value) {
			if (in_array($key, $keys) || empty($keys)) {
				$params[':' . $key] = $value;
			}
		}
		return $params;
	}

	/**
	 * Return Assoc array of Primary Key / Values
	 * NOTE: used for MeekroDB updates
	 * @param  Record $data
	 * @return array
	 */
	protected function getRecordPrimaryKeyValuesArray(Record $data) : array
	{
		$keyvals = [];

		foreach (static::PRIMARYKEY as $key) {
			$keyvals[$key] = $data->$key;
		}
		return $keyvals;
	}


	/**
	 * Return if Record has the needed column as keys
	 * @param  array $record
	 * @return bool
	 */
	protected function validateArrayKeys(array $record) : bool
	{
		foreach (array_keys(static::COLUMNS) as $col) {
			if (array_key_exists($col, $record) === false) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Return if array has Primary Keys
	 * @param  array $r
	 * @return bool
	 */
	protected function arrayHasRecordKeys(array $r) : bool
	{
		foreach (static::RECORDKEY as $col) {
			if (array_key_exists($col, $r) === false) {
				return false;
			}
		}
		return true;
	}
}