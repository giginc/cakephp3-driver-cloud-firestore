<?php
declare(strict_types=1);

namespace Giginc\CloudFirestore\Database\Driver;

use Exception;
use Google\Cloud\Firestore\FirestoreClient as CloudFirestoreClient;

/**
 * CloudFirestore
 *
 * @copyright Copyright (c) 2021,GIG inc.
 * @author Shota KAGAWA <kagawa@giginc.co.jp>
 */
class CloudFirestore
{
    /**
     * Config
     *
     * @var array
     * @access private
     */
    private $_config;

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
     * Database Instance
     *
     * @var \Giginc\CloudFirestore\Database
     * @access protected
     */
    protected $_db = null;

    /**
     * Base Config
     *
     * @var array
     * @access public
     *
     */
    protected $_baseConfig = [
        'projectId' => null,
        'keyFile' => [], // json
        'keyFilePath' => null,
        'retries' => 3,
    ];

    /**
     * @param array $config configuration
     */
    public function __construct($config)
    {
        $this->_config = array_merge($this->_baseConfig, $config);
    }

    /**
     * return configuration
     *
     * @param string $key key
     * @return array
     * @access public
     */
    public function getConfig(string $key = '')
    {
        if ($key) {
            return $this->_config[$key];
        } else {
            return $this->_config;
        }
    }

    /**
     * connect to the database
     *
     * @param string $name CloudFirestore file name.
     * @access public
     * @return bool
     */
    public function connect(string $name)
    {
        try {
            $config = [
                'projectId' => $this->_config['projectId'],
                'retries' => $this->_config['retries'],
            ];

            // config keyFile
            if ($this->_config['keyFile']) {
                $config['keyFile'] = $this->_config['keyFile'];
            }
            // config keyFilePath
            if ($this->_config['keyFilePath']) {
                $config['keyFilePath'] = $this->_config['keyFilePath'];
            }

            $this->_db = new CloudFirestoreClient($config);

            $this->connected = true;
        } catch (Exception $e) {
            trigger_error($e->getMessage());
        }

        return $this->connected;
    }

    /**
     * return database connection
     *
     * @param string $name Csv file name.
     * @access public
     * @return \Giginc\Csv\Database\Driver\File
     */
    public function getConnection($name)
    {
        if (!$this->isConnected()) {
            $this->connect($name);
        }

        return $this->_db;
    }

    /**
     * disconnect from the database
     *
     * @return bool
     * @access public
     */
    public function disconnect()
    {
        if ($this->connected) {
            return $this->connected = false;
        }

        return true;
    }

    /**
     * database connection status
     *
     * @return bool
     * @access public
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * @return bool
     */
    public function enabled()
    {
        return true;
    }
}
