<?php
declare(strict_types=1);

namespace Giginc\CloudFirestore\Test\TestCase\ORM;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;

/**
 * Cloud Firestore Test Table
 *
 * @uses Table
 * @copyright Copyright (c) 2021,GIG inc.
 * @author Shota KAGAWA <kagawa@giginc.co.jp>
 */
class CloudFirestoreTableTest extends Table
{
    /**
     * initialize
     *
     * @param array $config Config
     * @access public
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('tests');
        $this->setEntityClass('Giginc\CloudFirestore\Test\TestCase\ORM\CloudFirestoreEntityTest');
        $this->setConnection(ConnectionManager::get('test', false));
    }
}
