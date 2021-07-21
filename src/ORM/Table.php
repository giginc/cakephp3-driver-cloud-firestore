<?php
declare(strict_types=1);

namespace Giginc\CloudFirestore\ORM;

use BadMethodCallException;
use Cake\ORM\Table as CakeTable;
use Exception;
use Giginc\CloudFirestore\Database\Driver\CloudFirestore;

class Table extends CakeTable
{
    protected $_driver;

    protected $_db;

    private $_collection;

    private $_document;

    /**
     * Are we connected to the DataSource?
     *
     * true - yes
     * false - nope, and we can't connect
     *
     * @var bool
     * @access public
     */
    public $connected = false;

    /**
     * The schema object containing a description of this table fields
     *
     * @var \Cake\Database\Schema\TableSchema
     */
    protected $_schema;

    /**
     * return CloudFirestore
     *
     * @return \Giginc\CloudFirestore\ORM\file
     * @throws \Exception
     */
    private function _getConnection()
    {
        if ($this->connected === false) {
            $this->_driver = $this->getConnection()->getDriver();

            if (!$this->_driver instanceof CloudFirestore) {
                throw new Exception("Driver must be an instance of 'Giginc\CloudFirestore\Database\Driver\CloudFirestore'"); // phpcs:ignore
            }
            $this->_db = $this->_driver->getConnection($this->getTable());

            $this->connected = true;
        }

        return $this->_db;
    }

    /**
     * Sets the schema table object describing this table's properties.
     *
     * If an array is passed, a new TableSchema will be constructed
     * out of it and used as the schema for this table.
     *
     * @param array|\Cake\Database\Schema\TableSchema $schema Schema to be used for this table
     * @return $this
     */
    public function setSchema($schema)
    {
        if (is_array($schema)) {
            $this->_schema = $schema;
        }

        return $this;
    }

    /**
     * Closes the current datasource connection.
     *
     * @access public
     * @return void
     */
    public function disconnect()
    {
        $this->_driver->disconnect();
    }

    /**
     * client
     *
     * @access public
     * @return array
     */
    public function client()
    {
        $connection = $this->_getConnection();

        return $this->_db;
    }

    /**
     * getCollection
     *
     * @access private
     * @return string
     */
    private function getCollection()
    {
        $collection = $this->_collection;

        if (!$collection) {
            return $this->getTable();
        }

        // not first /
        if (!preg_match("/^\//", $collection)) {
            $collection = "/{$collection}";
        }

        // end / trim
        if (preg_match("/\/$/", $collection)) {
            $collection = preg_replace("/\/$/", '', $collection);
        }

        return $this->getTable() . $collection;
    }

    /**
     * getDocument
     *
     * @access private
     * @return string
     */
    private function getDocument()
    {
        $document = $this->_document;

        if (!$document) {
            return $this->getTable();
        }

        // not first /
        if (!preg_match("/^\//", $document)) {
            $document = "/{$document}";
        }

        // end / trim
        if (preg_match("/\/$/", $document)) {
            $document = preg_replace("/\/$/", '', $document);
        }

        return $this->getTable() . $document;
    }

    /**
     * getResponse
     *
     * @param \Cake\ORM\Query $snapshot Snapshot
     * @access private
     * @return array
     */
    private function getResponse($snapshot)
    {
        $entity = $this->getEntityClass();

        $snapshot = new $entity($snapshot->data());

        return $snapshot;
    }

    /**
     * find documents
     *
     * @param string $type Type.
     * @param array $options Option.
     * @access public
     * @return array
     * @throws \Exception
     */
    public function find($type = 'all', $options = [])
    {
        if ($type == 'all') {
        } else {
            $finder = 'find' . ucfirst($type);
            if (method_exists($this, $finder)) {
                $this->{$finder}($query, $options);
            } else {
                throw new BadMethodCallException(
                    sprintf('Unknown finder method "%s"', $type)
                );
            }
        }

        return $this;
    }

    /**
     * collection
     *
     * @param string $collection Collection
     * @access public
     * @return object
     */
    public function collection(string $collection)
    {
        $this->_collection = $collection;

        return $this;
    }

    /**
     * document
     *
     * @param string $document Document
     * @access public
     * @return object
     */
    public function document(string $document)
    {
        $this->_document = $document;

        return $this;
    }

    /**
     * select
     *
     * @access public
     * @return object
     */
    public function select()
    {
        $connection = $this->_getConnection();

        $document = $this->_db->document($this->getDocument());

        return $this->getResponse($document->snapshot());
    }

    /**
     * insert
     * instantiate the Cloud Firestore document service
     *
     * @param array $data Data
     * @access public
     * @return object
     */
    public function insert(array $data = [])
    {
        $connection = $this->_getConnection();

        $collection = $this->_db->collection($this->getCollection());
        $document = $collection->newDocument();

        return $document->create($data);
    }

    /**
     * set
     *
     * @param array $data Data
     * @access public
     * @return object
     */
    public function set(array $data = [])
    {
        $connection = $this->_getConnection();

        $document = $this->_db->document($this->getDocument());

        return $document->set($data);
    }

    /**
     * update
     *
     * @param array $data Data
     * @access public
     * @return object
     */
    public function update(array $data = [])
    {
        $connection = $this->_getConnection();

        $document = $this->_db->document($this->getDocument());

        return $document->update($data);
    }

    /**
     * remove
     *
     * @access public
     * @return object
     */
    public function remove()
    {
        $connection = $this->_getConnection();

        $document = $this->_db->document($this->getDocument());

        return $document->delete();
    }

    /**
     * id
     *
     * @access public
     * @return object
     */
    public function id()
    {
        $connection = $this->_getConnection();

        $document = $this->_db->document($this->getDocument());

        return $document->id();
    }

    /**
     * name
     *
     * @access public
     * @return object
     */
    public function name()
    {
        $connection = $this->_getConnection();

        $document = $this->_db->document($this->getDocument());

        return $document->name();
    }

    /**
     * parent
     *
     * @access public
     * @return object
     */
    public function parent()
    {
        $connection = $this->_getConnection();

        $document = $this->_db->document($this->getDocument());

        return $document->parent();
    }

    /**
     * path
     *
     * @access public
     * @return object
     */
    public function path()
    {
        $connection = $this->_getConnection();

        $document = $this->_db->document($this->getDocument());

        return $document->path();
    }
}
