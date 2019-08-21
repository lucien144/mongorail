<?php

namespace MongoRail\Repository;

use Doctrine\Common\Inflector\Inflector;
use MongoDB\Collection;
use MongoDB\Database;
use MongoRail\Connection;
use MongoRail\Exceptions;

/**
 * Class Base
 * @package MongoRail\Repository
 */
abstract class Base
{
	protected $collectionName;
	
	/** @var  Connection */
	protected $connection;
	
	/** @var  Database */
	protected $database;
	
	/** @var  Collection */
	protected $collection;
	
	/** @var bool */
	protected $strict = TRUE;
	
	private $filter = [];
	private $sort = [];
	
	
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
		$this->database = $this->connection->getDatabase();
		$this->getCollection();
	}
	
	
	protected function getCollection()
	{
		$ref = new \ReflectionClass($this);
		$this->collectionName = mb_strtolower($ref->getShortName());
		if (1 !== iterator_count($this->database->listCollections(['filter' => ['name' => $this->collectionName]]))) {
			throw new Exceptions\UnsetCollection($this->collectionName);
		}
		$this->collection = $this->database->selectCollection($this->collectionName);
	}
	
	
	/**
	 * Get the database name.
	 *
	 * @return string
	 */
	public function getDatabaseName()
	{
		return $this->database->getDatabaseName();
	}
	
	
	/**
	 * Check the server stats.
	 *
	 * @return array
	 */
	public function serverStatus()
	{
		return $this->database->command(['serverStatus' => TRUE])->toArray();
	}
	
	
	public function getEntityClassName()
	{
		$ref = new \ReflectionClass($this);
		throw new Exceptions\UnsetEntityClassName($ref->getName());
	}
	
	
	/**
	 * Filter and return one document.
	 *
	 * @param array $where
	 * @return array|null|object
	 */
	public function find(array $where)
	{
		return $this->collection->findOne($where);
	}
	
	
	/**
	 * Find documents based on filter & order.
	 *
	 * @param null $limit
	 * @param null $offset
	 * @return array
	 */
	public function findAll($limit = NULL, $offset = NULL, $yield = FALSE)
	{
		$generator = function() use ($limit, $offset) {
			$options = [
				'sort'  => $this->sort,
				'limit' => $limit,
				'skip'  => $offset,
			];
			$result = $this->collection->find($this->filter, $options);
			if (count($result) > 0) {
				foreach ($result as $row) {
					yield $this->documentToEntity($row);
				}
			}
		};

		if ($yield) {
			return $generator();
		} else {
			return iterator_to_array($generator());
		}
	}
	
	
	/**
	 * Get one document based on the document ID.
	 *
	 * @param $id
	 * @return null|object
	 */
	public function get($id)
	{
		$result = $this->collection->findOne(['_id' => new \MongoDB\BSON\ObjectID($id)]);
		return $this->documentToEntity($result);
	}
	
	
	/**
	 * Get number of random documents.
	 *
	 * @param $limit
	 * @return array
	 */
	public function getRand($limit)
	{
		$result = $this->collection->aggregate([
			['$match' => $this->filter],
			['$sample' => ['size' => $limit]],
		]);
		
		$data = [];
		if (count($result) > 0) {
			foreach ($result as $row) {
				$data[] = $this->documentToEntity($row);
			}
		}
		return $data;
	}
	
	
	/**
	 * Insert document.
	 *
	 * @param $data
	 * @return string
	 */
	public function insert($data)
	{
		$result = $this->collection->insertOne($data);
		return (string) $result->getInsertedId();
	}
	
	
	/**
	 * Delete document based on the ID.
	 *
	 * @param $id
	 */
	public function delete($id)
	{
		$this->collection->deleteOne(['_id' => new \MongoDB\BSON\ObjectID($id)]);
	}
	
	
	/**
	 * Update document.
	 *
	 * @param $id
	 * @param $data
	 */
	public function update($id, $data)
	{
		$this->collection->findOneAndUpdate(['_id' => new \MongoDB\BSON\ObjectID($id)], ['$set' => $data]);
	}
	
	
	/**
	 * Count documents in the collection.
	 *
	 * @return int
	 */
	public function count()
	{
		return $this->collection->count($this->filter);
	}
	
	
	/**
	 * Setup the where search.
	 *
	 * @param array $where
	 * @return $this
	 */
	public function where(array $where)
	{
		$this->filter += $where;
		return $this;
	}
	
	
	/**
	 * Order the resutls.
	 *
	 * @param $column
	 * @param string $asc
	 * @return $this
	 */
	public function orderby($column, $asc = 'ASC')
	{
		$asc = mb_strtolower($asc) === 'desc' ? 1 : -1;
		$this->sort += [$column => $asc];
		return $this;
	}
	
	
	/**
	 * Sets the strict parameter. If strict is on, even non-declared entity props are returned.
	 * Helpful when the entity structure is variable.
	 *
	 * @param $strict
	 * @return $this
	 */
	public function strict($strict)
	{
		$this->strict = $strict;
		return $this;
	}
	
	
	/**
	 * @param \MongoDB\Model\BSONDocument|object|null $document
	 * @return object|null
	 */
	protected function documentToEntity($document)
	{
		if ($document) {
			$entityName = $this->getEntityClassName();
			return new $entityName($document, $this->strict);
		}
		return NULL;
	}
}