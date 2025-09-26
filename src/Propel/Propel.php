<?php namespace Pauldro\Minicli\Database\Propel;
// Propel ORM Library
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Propel as PropelRuntime;
use Propel\Runtime\ServiceContainer\StandardServiceContainer;
use Propel\Runtime\Connection\ConnectionWrapper;
// Dplus
use Pauldro\Minicli\Database\Credentials;

/**
 * Wrapper Class for providing functions to create Propel ORM connections
 */
class Propel {
	/**
	 * Return ConnectionManager
	 * @param  Credentials $db
	 * @return ConnectionManagerSingle
	 */
	public static function propelConnectionManager(Credentials $db) : ConnectionManagerSingle 
	{
		$manager = new ConnectionManagerSingle($db->propelName);
		$manager->setConfiguration(self::propelConfiguration($db));
		return $manager;
	}

	/**
	 * Returns Propel connection Configuration
	 * @param  Credentials $db
	 * @return array
	 */
	public static function propelConfiguration(Credentials $db) : array
	{
		return [
			'classname' => 'Propel\\Runtime\\Connection\\ConnectionWrapper',
			'dsn' => "mysql:host=$db->host;dbname=$db->name",
			'user' => $db->user,
			'password' => $db->password,
			'attributes' => [
				'ATTR_EMULATE_PREPARES' => false,
				'ATTR_TIMEOUT' => 30,
			],
			'model_paths' => [
				0 => 'src',
				1 => 'vendor',
			],
		];
	}

	/**
	 * Return Service Container
	 * @return StandardServiceContainer
	 */
	public static function getServiceContainer() : StandardServiceContainer
	{
		return PropelRuntime::getServiceContainer();
	}

	/**
	 * Return Write Connection
	 * @param  string $name
	 * @return ConnectionWrapper
	 */
	public static function getConnection($name) : ConnectionWrapper
	{
		return PropelRuntime::getConnection($name);
	}

	/**
	 * Return Connection Interface for debug
	 * @return ConnectionWrapper
	 */
	public static function getConnectionDebug($name) : ConnectionWrapper
	{
		$conn = self::getConnection($name);
		$conn->useDebug(true);
		return $conn;
	}
}
