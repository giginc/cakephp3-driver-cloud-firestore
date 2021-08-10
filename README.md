Cloud Firestore Driver for Cakephp3
========

An Cloud Firestore for CakePHP 3.5,3.6,3.7

## Installing via composer

Install [composer](http://getcomposer.org) and run:

```bash
composer require giginc/cakephp3-driver-cloud-firestore
```

## Defining a connection
Now, you need to set the connection in your config/app.php file:

```php
 'Datasources' => [
...
    'cloud-firestore' => [
        'className' => 'Giginc\CloudFirestore\Database\Connection',
        'driver' => 'Giginc\CloudFirestore\Database\Driver\CloudFirestore',
        'projectId' => env('CLOUD_FIRESTORE_PROJECT_ID', 'project_id'),
        'keyFile' => [], // Console. Ex: json_decode(file_get_contents($path), true).
        'keyFilePath' => null, //The full path to your service account credentials .json file retrieved.
        'retries' => 3,
    ],

],
```

## Models
After that, you need to load Giginc\CloudFirestore\ORM\Table in your tables class:

### Table
```php
//src/Model/Table/ProductsTable.php
namespace App\Model\Table;

use Giginc\CloudFirestore\ORM\Table;

/**
 * ProductsTable Table
 *
 * @uses Table
 * @package Table
 */
class ProductsTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('products');
    }

    public static function defaultConnectionName()
    {
        return 'cloud-firestore';
    }
}
```

### Entity
```php
//src/Model/Entity/Product.php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Product Entity
 *
 * @uses Entity
 * @package Entity
 */
class Product extends Entity
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];

    protected $_virtual = [
    ];
}

## Controllers

```php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Pages Controller
 *
 * @property \App\Model\Table\PagesTable $Pages
 *
 * @method \App\Model\Entity\Review[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class PagesController extends AppController
{
    /**
     * Index method
     *
     * @access public
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $this->loadModel('Products');

        // select
        $data = $this->Products->find()
            ->document('1')
            ->first();

        // insert
        $this->Products->document('1')
            ->insert([
                'name' => 'iPhoneXR',
                'description' => 'iPhoneXR',
                'created_at' => '2021-04-21',
            ]);
    }
}
```

## LICENSE

[The MIT License (MIT) Copyright (c) 2021](http://opensource.org/licenses/MIT)
