<?php
/**
 * Created by PhpStorm.
 * User: janblasko
 * Date: 23/10/17
 * Time: 4:53 PM
 */

namespace MongoRail;

use MongoDB\Client;
use MongoDB\Database;
use MongoRail\Exceptions\ConnectionSingletonError;

class Connection
{
	/** @var  Client */
	private static $connection;
	
	/** @var  Database */
	private static $database;
	
	/** @var  Connection */
	private static $_instance;
	
	
	public function __construct()
	{
	}
	
	
	public static function connect(string $dsn): Connection
	{
		if (!self::$_instance) {
			self::$_instance = new Connection();
			self::$connection = new \MongoDB\Client($dsn);
			preg_match('/^mongodb:\\/\\/.+\\/(.+)$/s', $dsn, $matches);
			self::$database = self::$connection->selectDatabase($matches[1] ?? '');
		}
		return self::$_instance;
	}
	
	
	public function getConnection()
	{
		return self::$connection;
	}
	
	
	public function getDatabase()
	{
		return self::$database;
	}
	
	
}