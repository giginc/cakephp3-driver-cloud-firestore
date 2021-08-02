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
    private $_query;
    private $_path = '';

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

        unset($this->_query);
        $this->_path = '';
        $this->connected = false;
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

        return $connection;
    }

    /**
     * getPath
     *
     * @param string $path Path
     * @access private
     * @return string
     */
    private function getPath(string $path)
    {
        // not first /
        if (!preg_match("/^\//", $path)) {
            $path = "/{$path}";
        }

        // end / trim
        if (preg_match("/\/$/", $path)) {
            $path = preg_replace("/\/$/", '', $path);
        }

        // set path
        $regex = "^[\/]{0,1}" . $this->getTable();
        if (preg_match("/{$regex}/", $path)) {
            $path = preg_replace("/{$regex}/", '', $path);
        }

        if (!$this->_path) {
            $path = $this->getTable() . $path;
        }

        $this->_path .= $path;

        return $path;
    }

    /**
     * getCollection
     *
     * @param string $collection Collection
     * @access private
     * @return string
     */
    private function getCollection(string $collection = '')
    {
        if (!$collection) {
            $collection = $this->getTable();
        }

        return $this->getPath($collection);
    }

    /**
     * getDocument
     *
     * @param string $document Document
     * @access private
     * @return string
     */
    private function getDocument(string $document = '')
    {
        if (!$document) {
            return $this->getTable();
        }

        return $this->getPath($document);
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
        $response = null;
        $entity = $this->getEntityClass();

        $class = get_class($snapshot);
        if ($snapshot instanceof \Google\Cloud\Firestore\QuerySnapshot) {
            $response = [];
            foreach ($snapshot as $row) {
                $properties = array_merge(['id' => $row->id()], $row->data());
                $response[] = new $entity($properties);
            }
        } else {
            if ($snapshot->exists()) {
                $properties = array_merge(['id' => $snapshot->id()], $snapshot->data());
                $response = new $entity($properties);
            }
        }

        return $response;
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
    public function collection(string $collection = '')
    {
        $collection = $this->getCollection($collection);
        $this->_collection = $this->client()->collection($collection);
        $this->_query = $this->_collection;

        return $this;
    }

    /**
     * document
     *
     * @param string $document Document
     * @access public
     * @return object
     */
    public function document(string $document = '')
    {
        $document = $this->getDocument($document);
        $this->_document = $this->client()->document($document);
        $this->_query = $this->_document;

        return $this;
    }

    /**
     * first
     *
     * @access public
     * @return object
     */
    public function first()
    {
        $snapshot = $this->_query->snapshot();

        $response = $this->getResponse($snapshot);

        $this->disconnect();

        return $response;
    }

    /**
     * all
     *
     * @access public
     * @return object
     */
    public function all()
    {
        $snapshot = $this->_query->documents();

        $response = $this->getResponse($snapshot);

        $this->disconnect();

        return $response;
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
        $document = $this->_query->newDocument();
        $this->_query = $document->create($data);

        return $this;
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
        $this->_query = $this->_query->set($data);

        return $this;
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
        $this->_query = $this->_query->update($data);

        return $this;
    }

    /**
     * remove
     *
     * @access public
     * @return object
     */
    public function remove()
    {
        $this->_query = $this->_query->delete();

        return $this;
    }

    /**
     * id
     *
     * @access public
     * @return object
     */
    public function id()
    {
        $this->_query = $this->_query->id();

        return $this;
    }

    /**
     * name
     *
     * @access public
     * @return object
     */
    public function name()
    {
        $this->_query = $this->_query->name();

        return $this;
    }

    /**
     * parent
     *
     * @access public
     * @return object
     */
    public function parent()
    {
        $this->_query = $this->_query->parent();

        return $this;
    }

    /**
     * path
     *
     * @access public
     * @return object
     */
    public function path()
    {
        $this->_query = $this->_query->path();

        return $this;
    }

    /**
     * order
     *
     * @param string $field Field
     * @param string $direction Direction default:ASC
     * @access public
     * @return object
     */
    public function order(string $field, string $direction = 'ASC')
    {
        $this->_query = $this->_query->orderBy($field, $direction);

        return $this;
    }
}
