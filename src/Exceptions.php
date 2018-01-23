<?php
/**
 * Created by PhpStorm.
 * User: janblasko
 * Date: 22/07/2017
 * Time: 22:11
 */

namespace MongoRail\Exceptions;


use Throwable;

class UnsetCollection extends \Exception
{
	public $msg = 'Can\'t find correct database collection with name "%s". Did you create it? Repository name needs to have same name.';
	
	public function __construct($message = "", $code = 0, Throwable $previous = NULL)
	{
		parent::__construct(sprintf($this->msg, $message), $code, $previous);
	}
}

class UnknownDatabaseEnvironment extends \Exception
{
}

class ConnectionSingletonError extends \Exception
{
	public $message = 'Connection is singleton. Please, use Connection::connect() instead.';
}

class UnknownEntityProperty extends \Exception
{
	public $msg = 'You need to declare type of the property "%s"';
	
	
	public function __construct($message = "", $code = 0, Throwable $previous = NULL)
	{
		parent::__construct(sprintf($this->msg, $message), $code, $previous);
	}
}

class UnknownEntityType extends \Exception
{
	public $msg = 'Unknown property type "%s"';
	
	
	public function __construct($message = "", $code = 0, Throwable $previous = NULL)
	{
		parent::__construct(sprintf($this->msg, $message), $code, $previous);
	}
}

class UnsetEntityClassName extends \Exception
{
	public $msg = 'Unset entity class for repository "%s". Did you forget to override the Repository::getEntityClassName()?';
	
	
	public function __construct($message = "", $code = 0, Throwable $previous = NULL)
	{
		parent::__construct(sprintf($this->msg, $message), $code, $previous);
	}
}

