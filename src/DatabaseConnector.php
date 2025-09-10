<?php namespace Pauldro\Minicli\Database;
// Base PHP
use PDO, PDOException;
// Propel ORM Library
use Propel\Runtime\Connection\ConnectionWrapper as PropelConnection;
use Propel\Runtime\Connection\Exception\ConnectionException as PropelConnectionException;
// Meekro DB
use MeekroDB;
use Pauldro\Minicli\Database\Propel\Propel;

/**
 * DatabaseConnector
 * Makes Database Connection
 *
 * @property PDO|false $pdo
 * @property MeekroDB $meekrodb
 * @property PropelConnection $propel
 */
class DatabaseConnector {
    public $errorMsg = '';
	private $pdo = false;
	private $meekrodb = false;
    private $db;
    private $propel = false;
	private $name = '';

	public function __construct(Credentials $creds, $name = 'default') {
        $this->db   = $creds;
		$this->name = $name;
	}

	public function __sleep() {
		return ['meekrodb', 'db', 'propel', 'name'];
	}

/* =============================================================
	Public
============================================================= */
    /**
	 * Return if db is able to connect
	 */
	public function connect() : bool
    {
		if ($this->connectPdo() === false) {
			return false;
		}
        if ($this->connectMeekroDb() === false) {
            return false;
        }
        if (empty($this->db->propelName)) {
            return true;
        }
        if ($this->connectPropel() === false) {
            return false;
        }
		return true;
	}

    /**
	 * Return PDO Connection
	 * @return PDO|bool
	 */
	public function pdo() : mixed
    {
		if (empty($this->pdo)) {
			return false;
		}
		return $this->pdo;
	}

	/**
	 * Return Meekro DB Connection
	 * @return MeekroDB|bool
	 */
	public function meekrodb() : mixed
    {
		if (empty($this->meekrodb)) {
			return false;
		}
		return $this->meekrodb;
	}

	/**
	 * @return PropelConnection|bool
	 */
    public function propel() : mixed
	{
        if (empty($this->propel)) {
			return false;
		}
		return $this->propel;
    }

/* =============================================================
	Internal Processing
============================================================= */
	/**
	 * Return if PDO connection was able to be made
	 * @return bool
	 */
	private function connectPdo() : bool
    {
		if (empty($this->pdo) === false) {
			return true;
		}

		try {
            $db = $this->db;
			$pdo = new PDO($this->generateDsnFromDbCreds(), $db->user, $db->password);
		} catch(PDOException $e) {
            $this->errorMsg = $e->getMessage();
			return false;
		}
		$this->pdo = $pdo;
		return true;
	}

    /**
	 * Initialize Meekro DB
	 */
	private function connectMeekroDb() : bool
    {
		$db = $this->db;
		$meekrodb = new MeekroDB($this->generateDsnFromDbCreds(), $db->user, $db->password);
		$this->meekrodb = $meekrodb;
		return true;
	}

    /**
	 * Initialize Propel DB connection
	 */
    private function connectPropel() : bool
    {
        $manager = Propel::propelConnectionManager($this->db);
		$service = Propel::getServiceContainer();
		$service->checkVersion(2);
		$service->setAdapterClass($this->db->propelName, 'mysql');
		$service->setConnectionManager($this->db->propelName, $manager);

		if ($this->db->isPropelDefault) {
			$service->setDefaultDatasource($this->db->propelName);
		}

        try {
            $this->propel = Propel::getConnectionDebug($this->db->propelName);
        } catch (PropelConnectionException $e) {
            $this->errorMsg = "Failed to connect Propel Database";
            return false;
        }
        return true;
    }

    /**
	 * Return DSN from DB Creds
	 */
	private function generateDsnFromDbCreds() : string
    {
        $db = $this->db;
		return "mysql:host=$db->host;port=$db->port;dbname=$db->name";
	}
}
