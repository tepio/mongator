<?php

namespace Mongator\Tests\Extension;

use Mongator\Tests\TestCase;
use Mongator\Query\Query;

class QueryDefaultFindersTest extends TestCase
{
    private $repository;

    protected function setUp()
    {
        parent::setUp();
        $this->repository = $this->mongator->getRepository('Model\FieldTypeExamples');
    }

    public function testFindById()
    {
        $id = new \MongoId();
        $query = $this->createQuery()->findById($id);
        $this->assertEquals(array('_id' => $id), $query->getCriteria());
    }

    public function testFindByIdUsesIdToMongo()
    {
        $id = new \MongoId();
        $query = $this->createQuery()->findById((string) $id);
        $this->assertEquals(array('_id' => $id), $query->getCriteria());
    }

    public function testFindByIds()
    {
        $ids = array(new \MongoId(), new \MongoId(), new \MongoId());
        $query = $this->createQuery()->findByIds($ids);
        $this->assertEquals(array('_id' => array('$in' => $ids)), $query->getCriteria());
    }

    public function testFindByFields()
    {
        $query = $this->createQuery()
            ->findByName('myname')
            ->findByPosition(3)
            ->findByIsActive(true)
        ;

        $this->assertSame(
            array(
                'name' => 'myname',
                'pos' => 3,
                'isActive' => true,
            ),
            $query->getCriteria()
        );
    }

    public function testDateCasting()
    {
        $date = new \DateTime();
        $mongoDate = new \MongoDate($date->getTimestamp());
        $expected = array('date' => $mongoDate);

        $query = $this->createQuery()->findByDate($mongoDate);
        $this->assertEquals($expected, $query->getCriteria());

        $query = $this->createQuery()->findByDate($date);
        $this->assertEquals($expected, $query->getCriteria());

        $query = $this->createQuery()->findByDate($date->getTimestamp());
        $this->assertEquals($expected, $query->getCriteria());
    }

    public function testIntTypecheck()
    {
        $this->setExpectedException('\Exception');
        $this->createQuery()->findByPosition('1');
    }

    public function testFloatTypecheck()
    {
        $this->setExpectedException('\Exception');
        $this->createQuery()->findByAvg('1');
    }

    public function testStringTypecheck()
    {
        $this->setExpectedException('\Exception');
        $this->createQuery()->findByName(33);
    }

    public function testDateTypecheck()
    {
        $this->setExpectedException('\Exception');
        $this->createQuery()->findByDate('2013-05-17');
    }

    public function testFindByReferencesOne()
    {
        $this->doTestFindByReference('findByAuthor', 'Model\Author', 'author');
    }

    public function testFindByReferencesMany()
    {
        $this->doTestFindByReference('findByCategories', 'Model\Category', 'categories');
    }

    private function doTestFindByReference($method, $class, $field)
    {
        $id = new \MongoId();
        $expected = array($field => $id);

        $query = $this->createQuery()->{$method}($id);
        $this->assertEquals($expected, $query->getCriteria());

        $object = $this->mongator->create($class)->setId($id);
        $query = $this->createQuery()->{$method}($id);
        $this->assertEquals($expected, $query->getCriteria());
    }

    public function testFindByReferenceTypecheck()
    {
        $this->setExpectedException('\Exception');
        $this->createQuery()->findByAuthor(1234);
    }

    public function testFindByReferenceAcceptsString()
    {
        $id = new \MongoId();
        $query = $this->createQuery()->findByAuthor((string) $id);
        $this->assertEquals(array('author' => $id), $query->getCriteria());
    }

    public function testIgnoreUnsearchableTypes()
    {
        $query = $this->createQuery();

        $this->assertFalse(method_exists($query, 'findByBindata'));
        $this->assertFalse(method_exists($query, 'findByRawdata'));
        $this->assertFalse(method_exists($query, 'findBySerializeddata'));
    }

    public function testFindByReferencesOneIds()
    {
        $this->doTestFindByReferenceIds('findByAuthorIds', 'author');
    }

    public function testFindByReferencesManyIds()
    {
        $this->doTestFindByReferenceIds('findByCategoriesIds', 'categories');
    }

    private function doTestFindByReferenceIds($method, $field)
    {
        $query = $this->createQuery();

        $ids = array(new \MongoId(), new \MongoId(), new \MongoId());
        $query->{$method}($ids);
        $expected = array($field => array('$in' => $ids));
        $this->assertEquals($expected, $query->getCriteria());
    }

    private function createQuery()
    {
        return $this->repository->createQuery();
    }
}
