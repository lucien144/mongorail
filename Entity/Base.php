<?php
/**
 * Created by PhpStorm.
 * User: janblasko
 * Date: 24/10/17
 * Time: 9:56 AM
 */

namespace MongoRail\Entity;

use MongoDB;
use MongoRail\Exceptions\UnknownEntityProperty;
use MongoRail\Exceptions\UnknownEntityType;

class Base
{
	public function __construct($document = NULL)
	{
		if ($document === NULL) return;
		
		$ref = new \ReflectionClass($this);
		foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
			
			// TODO: Use tokenizer?
			if (!preg_match('/@var(\s+)(?P<type>[^\[\]\s]*)(?P<isArray>\[\])?/', $prop->getDocComment(), $typeHint)) {
				throw new UnknownEntityProperty($prop->name);
			}
			
			switch (TRUE) {
				case $prop->name === 'id' && isset($document->_id) && $document->_id instanceof MongoDB\BSON\ObjectID:
					/** @var MongoDB\BSON\ObjectID $oid */
					$oid = $document->_id;
					$this->id = $this->setType((string)$oid, $typeHint['type'], $typeHint['isArray'] ?? FALSE);
					break;
				
				case !isset($document->{$prop->name}):
					$this->{$prop->name} = NULL;
					break;
				
				default:
					$this->{$prop->name} = $this->setType($document->{$prop->name}, $typeHint['type'], $typeHint['isArray'] ?? FALSE);
					break;
			}
		}
	}
	
	
	protected function setType($var, $type, $isArray = FALSE)
	{
		if (in_array($type, ['boolean', 'bool', 'integer', 'int', 'float', 'double', 'string', 'array', 'null'])) {
			try {
				set_error_handler(function ($errno, $errstr, $errfile, $errline) { return E_RECOVERABLE_ERROR === $errno ? TRUE : FALSE; }); // Make the Catchable fatal error really catchable.
				settype($var, $type); // This may throw Catchable fatal error if the VAR cannot be converted to TYPE
				restore_error_handler(); // Restore previos error handler set by the app.
				return $var;
			} catch (\Throwable $e) {
				return NULL;
			}
		}
		
		if (in_array($type, ['object'])) {
			if ($var instanceof MongoDB\Model\BSONArray || $var instanceof MongoDB\Model\BSONDocument) {
				return $this->toObject($var);
			}
			return (object) $var;
		}
		
		if (class_exists($type)) {
			$data = NULL;
			switch (TRUE) {
				case $var instanceof MongoDB\Model\BSONArray:
					/** @var MongoDB\Model\BSONArray $var */
					$data = $var->bsonSerialize();
					if (!$isArray) {
						return new $type($data);
					} else {
						$arr = [];
						foreach ($data as $item) {
							$arr[] = new $type($item);
						}
						return $arr;
					}
				
				case $var instanceof MongoDB\Model\BSONDocument && $isArray:
					/** @var MongoDB\Model\BSONDocument $var */
					$data = $var->bsonSerialize();
					$arr = [];
					foreach ((array) $data as $key => $data) {
						$arr[$key] = new $type($data);
					}
					return $arr;
					
				case $var instanceof MongoDB\Model\BSONDocument:
					/** @var MongoDB\Model\BSONDocument $var */
					$data = $var->bsonSerialize();
					return new $type($data);
				
				default:
					if (!$isArray) {
						return new $type((object)$var);
					} else {
						$arr = [];
						foreach ($var as $item) {
							$arr[] = new $type((object)$item);
						}
						return $arr;
					}
			}
		}
		
		throw new UnknownEntityType(sprintf("%s (var: %s)", $type, $var));
	}
	
	
	private function toObject($var)
	{
		if ($var instanceof MongoDB\Model\BSONArray || $var instanceof MongoDB\Model\BSONDocument) {
			$var = $var->bsonSerialize();
		}
		
		if (is_array($var)) {
			foreach ($var as &$item) {
				$item = $this->toObject($item);
			}
		}
		
		if (is_object($var)) {
			foreach ($var as &$item) {
				$item = $this->toObject($item);
			}
		}
		
		return $var;
	}
}