<?php

use PHPUnit\Framework\TestCase;

class TreeWalkerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // getdiff
    // -------------------------------------------------------------------------

    public function testGetdiffSimpleStructs(): void
    {
        $treewalker = new TreeWalker(array(
            "debug" => false,
            "returntype" => "array"
        ));

        $struct1 = array('1' => array('2' => '7', '3' => array('4' => '6')));
        $struct2 = array('1' => array('3' => array('4' => '5')));

        $expectedResult = array(
            'edited'  => array('1/3/4' => array('newvalue' => '5', 'oldvalue' => '6')),
            'new'     => array(),
            'removed' => array('1/2' => '7')
        );

        $this->assertEquals($expectedResult, $treewalker->getdiff($struct2, $struct1, false));
    }

    public function testGetdiffWithArrayProperty(): void
    {
        $treewalker = new TreeWalker(array(
            "debug" => false,
            "returntype" => "array"
        ));

        $struct1 = array('a' => 1, 'b' => array(array('c1' => 1), array('c2' => 2)));
        $struct2 = array('a' => 11, 'b' => array(array('c1' => 1), array('c2' => 22)));

        $expectedResult = array(
            'edited'  => array(
                'a'      => array('newvalue' => 11, 'oldvalue' => 1),
                'b/1/c2' => array('newvalue' => 22, 'oldvalue' => 2)
            ),
            'new'     => array(),
            'removed' => array()
        );

        $this->assertEquals($expectedResult, $treewalker->getdiff($struct2, $struct1, false));
    }

    public function testGetdiffWithDifferentTypes(): void
    {
        $treewalker = new TreeWalker(array(
            "debug" => false,
            "returntype" => "array"
        ));

        $struct1 = array('a' => 1, 'b' => array('c1' => 2), 'c' => 3);
        $struct2 = array('a' => '1', 'b' => 2, 'c' => true);

        $expectedResult = array(
            'edited'  => array(
                'a' => array('newvalue' => '1', 'oldvalue' => 1),
                'c' => array('newvalue' => true, 'oldvalue' => 3)
            ),
            'new'     => array('b' => 2),
            'removed' => array('b/c1' => 2)
        );

        $this->assertEquals($expectedResult, $treewalker->getdiff($struct2, $struct1, false));
    }

    public function testGetdiffWithJsonInput(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $result = $treewalker->getdiff('{"a":1,"b":2}', '{"a":1,"b":3}', false);

        $this->assertArrayHasKey('edited', $result);
        $this->assertArrayHasKey('b', $result['edited']);
        $this->assertEquals(3, $result['edited']['b']['oldvalue']);
        $this->assertEquals(2, $result['edited']['b']['newvalue']);
    }

    public function testGetdiffWithObjectInput(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $result = $treewalker->getdiff((object)array('a' => 1, 'b' => 2), (object)array('a' => 1, 'b' => 3), false);

        $this->assertArrayHasKey('edited', $result);
        $this->assertArrayHasKey('b', $result['edited']);
    }

    public function testGetdiffSlashToObject(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $result = $treewalker->getdiff(array('a' => array('b' => 1)), array('a' => array('b' => 2)), true);

        $this->assertArrayHasKey('edited', $result);
        $this->assertArrayHasKey('a', $result['edited']);
        $this->assertArrayHasKey('b', $result['edited']['a']);
    }

    public function testGetdiffReturnsJsonString(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "jsonstring"));

        $result = $treewalker->getdiff(array('a' => 1), array('a' => 2), false);

        $this->assertIsString($result);
        $this->assertIsArray(json_decode($result, true));
    }

    public function testGetdiffReturnsObject(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "object"));

        $result = $treewalker->getdiff(array('a' => 1), array('a' => 2), false);

        $this->assertIsObject($result);
        $this->assertObjectHasProperty('edited', $result);
    }

    // -------------------------------------------------------------------------
    // getDynamicallyValue
    // -------------------------------------------------------------------------

    public function testGetDynamicallyValueReturnsScalar(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('a' => array('b' => array('c' => 42)));

        $this->assertEquals(42, $treewalker->getDynamicallyValue($struct, array('a', 'b', 'c')));
    }

    public function testGetDynamicallyValueReturnsNestedArray(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('a' => array('b' => array('x' => 1, 'y' => 2)));

        $this->assertEquals(array('x' => 1, 'y' => 2), $treewalker->getDynamicallyValue($struct, array('a', 'b')));
    }

    public function testGetDynamicallyValueWithJsonInput(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $this->assertEquals(99, $treewalker->getDynamicallyValue('{"a":{"b":99}}', array('a', 'b')));
    }

    // -------------------------------------------------------------------------
    // setDynamicallyValue
    // -------------------------------------------------------------------------

    public function testSetDynamicallyValueUpdatesNestedKey(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('a' => array('b' => 1));
        $result = $treewalker->setDynamicallyValue($struct, array('a', 'b'), 99);

        $this->assertEquals(array('a' => array('b' => 99)), $result);
    }

    public function testSetDynamicallyValueDoesNotMutateOriginal(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('a' => array('b' => 1));
        $treewalker->setDynamicallyValue($struct, array('a', 'b'), 99);

        $this->assertEquals(1, $struct['a']['b']);
    }

    // -------------------------------------------------------------------------
    // createDynamicallyObjects
    // -------------------------------------------------------------------------

    public function testCreateDynamicallyObjectsOnEmptyStruct(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $result = $treewalker->createDynamicallyObjects(array(), array('level1', 'level2'));

        $this->assertEquals(array('level1' => array('level2' => array())), $result);
    }

    public function testCreateDynamicallyObjectsAddsToExistingStruct(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('existing' => 1);
        $result = $treewalker->createDynamicallyObjects($struct, array('new', 'path'));

        $this->assertArrayHasKey('existing', $result);
        $this->assertEquals(array(), $result['new']['path']);
    }

    // -------------------------------------------------------------------------
    // walker
    // -------------------------------------------------------------------------

    public function testWalkerModifiesValues(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('a' => 1, 'b' => 2, 'c' => array('d' => 3));
        $result = $treewalker->walker($struct, function (&$struct, $key, &$value) {
            if (is_int($value)) {
                $value = $value * 2;
            }
        });

        $this->assertEquals(array('a' => 2, 'b' => 4, 'c' => array('d' => 6)), $result);
    }

    public function testWalkerDeletesNode(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('a' => 1, 'b' => 2, 'c' => 3);
        $result = $treewalker->walker($struct, function (&$struct, $key, &$value) {
            if ($key === 'b') {
                unset($struct[$key]);
            }
        });

        $this->assertEquals(array('a' => 1, 'c' => 3), $result);
    }

    // -------------------------------------------------------------------------
    // structMerge
    // -------------------------------------------------------------------------

    public function testStructMergeFirstOverridesSecond(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct1 = array('a' => 1, 'b' => 2);
        $struct2 = array('b' => 99, 'c' => 3);

        $result = $treewalker->structMerge($struct1, $struct2, false);

        $this->assertEquals(1, $result['a']);
        $this->assertEquals(2, $result['b']);
        $this->assertEquals(3, $result['c']);
    }

    public function testStructMergeSlashToObject(): void
    {
        $treewalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $result = $treewalker->structMerge(
            array('a' => array('b' => 1)),
            array('a' => array('c' => 2)),
            true
        );

        $this->assertArrayHasKey('a', $result);
        $this->assertArrayHasKey('b', $result['a']);
        $this->assertArrayHasKey('c', $result['a']);
    }

    // -------------------------------------------------------------------------
    // debug mode
    // -------------------------------------------------------------------------

    public function testDebugModeAddsTimeKey(): void
    {
        $treewalker = new TreeWalker(array("debug" => true, "returntype" => "array"));

        $result = $treewalker->getdiff(array('a' => 1), array('a' => 2), false);

        $this->assertArrayHasKey('time', $result);
        $this->assertStringContainsString('milliseconds', $result['time']);
    }
}
