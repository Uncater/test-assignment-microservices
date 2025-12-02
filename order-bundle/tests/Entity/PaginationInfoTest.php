<?php

namespace OrderBundle\Tests\Entity;

use OrderBundle\Entity\PaginationInfo;
use PHPUnit\Framework\TestCase;

class PaginationInfoTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $pagination = new PaginationInfo(100, 1, 10, 10);

        $this->assertEquals(100, $pagination->total);
        $this->assertEquals(1, $pagination->page);
        $this->assertEquals(10, $pagination->limit);
        $this->assertEquals(10, $pagination->pages);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $pagination = new PaginationInfo(100, 1, 10, 10);

        $array = $pagination->toArray();

        $expected = [
            'total' => 100,
            'page' => 1,
            'limit' => 10,
            'pages' => 10
        ];

        $this->assertEquals($expected, $array);
    }

    public function testCanCreateWithDifferentValues(): void
    {
        $pagination = new PaginationInfo(50, 2, 25, 2);

        $this->assertEquals(50, $pagination->total);
        $this->assertEquals(2, $pagination->page);
        $this->assertEquals(25, $pagination->limit);
        $this->assertEquals(2, $pagination->pages);
    }

    public function testCanHandleZeroValues(): void
    {
        $pagination = new PaginationInfo(0, 1, 10, 0);

        $this->assertEquals(0, $pagination->total);
        $this->assertEquals(1, $pagination->page);
        $this->assertEquals(10, $pagination->limit);
        $this->assertEquals(0, $pagination->pages);
    }

    public function testPropertiesAreReadonly(): void
    {
        $pagination = new PaginationInfo(100, 1, 10, 10);

        $reflection = new \ReflectionClass($pagination);
        
        $this->assertTrue($reflection->getProperty('total')->isReadOnly());
        $this->assertTrue($reflection->getProperty('page')->isReadOnly());
        $this->assertTrue($reflection->getProperty('limit')->isReadOnly());
        $this->assertTrue($reflection->getProperty('pages')->isReadOnly());
    }
}