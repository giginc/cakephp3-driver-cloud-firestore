<?php
declare(strict_types=1);

namespace Giginc\CloudFirestore\Test\TestCase\ORM;

use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tests Table class
 *
 * @uses TestCase
 * @copyright Copyright (c) 2021,GIG inc.
 * @author Shota KAGAWA <kagawa@giginc.co.jp>
 */
class TableTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'core.Articles',
        'core.Tags',
        'core.ArticlesTags',
        'core.Authors',
    ];

    /**
     * @var MongoTestsTable $table
     */
    public $table;

    /**
     * setUp
     *
     * @access public
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->table = TableRegistry::getTableLocator()->get('CloudFirestoreTableTest', [
            'className' => 'Giginc\CloudFirestore\Test\TestCase\ORM\CloudFirestoreTableTest',
        ]);
    }

    /**
     * tearDown
     *
     * @access public
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->table->deleteAll([]);
        TableRegistry::getTableLocator()->clear();
    }

    /**
     * Tests find('list')
     *
     * @return void
     */
    public function testFindListNoHydration()
    {
        $this->table->setDisplayField('username');
        $query = $this->table->find('list')
            ->document('test')
            ->order('id');

        $expected = [
            1 => 'mariano',
            2 => 'nate',
            3 => 'larry',
            4 => 'garrett',
        ];
        $this->assertSame($expected, $query->toArray());

        $query = $this->table->find('list', ['fields' => ['id', 'username']])
            ->enableHydration(false)
            ->order('id');
        $expected = [
            1 => 'mariano',
            2 => 'nate',
            3 => 'larry',
            4 => 'garrett',
        ];
        $this->assertSame($expected, $query->toArray());

        $query = $this->table->find('list', ['groupField' => 'odd'])
           ->select([
               'id',
               'username',
               'odd' => new FunctionExpression('MOD', [new IdentifierExpression('id'), 2]),
           ])
           ->enableHydration(false)
           ->order('id');
        $expected = [
            1 => [
                1 => 'mariano',
                3 => 'larry',
            ],
            0 => [
                2 => 'nate',
                4 => 'garrett',
            ],
        ];
        $this->assertSame($expected, $query->toArray());
    }

    /**
     * Tests find('list') with hydrated records
     *
     * @return void
     */
    public function testFindListHydrated()
    {
        $this->table->setDisplayField('username');
        $query = $this->table->find('list', ['fields' => ['id', 'username']])
                       ->order('id');
        $expected = [
            1 => 'mariano',
            2 => 'nate',
            3 => 'larry',
            4 => 'garrett',
        ];
        $this->assertSame($expected, $query->toArray());

        $query = $table->find('list', ['groupField' => 'odd'])
           ->select([
               'id',
               'username',
               'odd' => new FunctionExpression('MOD', [new IdentifierExpression('id'), 2]),
           ])
           ->enableHydration(true)
           ->order('id');
        $expected = [
            1 => [
                1 => 'mariano',
                3 => 'larry',
            ],
            0 => [
                2 => 'nate',
                4 => 'garrett',
            ],
        ];
        $this->assertSame($expected, $query->toArray());
    }

    /**
     * Test that the associated entities are unlinked and deleted when they have a not nullable foreign key
     *
     * @return void
     */
    public function testSaveReplaceSaveStrategyAdding()
    {
        $this->table->hasMany('Comments', ['saveStrategy' => 'replace']);

        $article = $this->table->newEntity([
            'title' => 'Bakeries are sky rocketing',
            'body' => 'All because of cake',
            'comments' => [
                [
                    'user_id' => 1,
                    'comment' => 'That is true!',
                ],
                [
                    'user_id' => 2,
                    'comment' => 'Of course',
                ],
            ],
        ], ['associated' => ['Comments']]);

        $article = $this->table->save($article, ['associated' => ['Comments']]);
        $commentId = $article->comments[0]->id;
        $sizeComments = count($article->comments);
        $articleId = $article->id;

        $this->assertEquals($sizeComments, $this->table->Comments->find('all')
                                                              ->where(['article_id' => $article->id])
                                                              ->count());
        $this->assertTrue($this->table->Comments->exists(['id' => $commentId]));

        unset($article->comments[0]);
        $article->comments[] = $this->table->Comments->newEntity([
            'user_id' => 1,
            'comment' => 'new comment',
        ]);

        $article->setDirty('comments', true);
        $article = $this->table->save($article, ['associated' => ['Comments']]);

        $this->assertEquals($sizeComments, $this->table->Comments->find('all')
            ->where(['article_id' => $article->id])
            ->count());
        $this->assertFalse($this->table->Comments->exists(['id' => $commentId]));
        $this->assertTrue($this->table->Comments->exists([
            'to_char(comment)' => 'new comment',
            'article_id' => $articleId,
        ]));
    }
}
