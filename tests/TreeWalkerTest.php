<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TreeWalker::class)]
class TreeWalkerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // getdiff
    // -------------------------------------------------------------------------

    public function testGetdiffSimpleStructs(): void
    {
        $treeWalker = new TreeWalker(array(
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

        $this->assertEquals($expectedResult, $treeWalker->getdiff($struct2, $struct1, false));
    }

    public function testGetdiffWithArrayProperty(): void
    {
        $treeWalker = new TreeWalker(array(
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

        $this->assertEquals($expectedResult, $treeWalker->getdiff($struct2, $struct1, false));
    }

    public function testGetdiffWithDifferentTypes(): void
    {
        $treeWalker = new TreeWalker(array(
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

        $this->assertEquals($expectedResult, $treeWalker->getdiff($struct2, $struct1, false));
    }

    public function testGetdiffWithJsonInput(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $result = $treeWalker->getdiff('{"a":1,"b":2}', '{"a":1,"b":3}', false);

        $this->assertArrayHasKey('edited', $result);
        $this->assertArrayHasKey('b', $result['edited']);
        $this->assertEquals(3, $result['edited']['b']['oldvalue']);
        $this->assertEquals(2, $result['edited']['b']['newvalue']);
    }

    public function testGetdiffWithObjectInput(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $result = $treeWalker->getdiff((object)array('a' => 1, 'b' => 2), (object)array('a' => 1, 'b' => 3), false);

        $this->assertArrayHasKey('edited', $result);
        $this->assertArrayHasKey('b', $result['edited']);
    }

    public function testGetdiffSlashToObject(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $result = $treeWalker->getdiff(array('a' => array('b' => 1)), array('a' => array('b' => 2)), true);

        $this->assertArrayHasKey('edited', $result);
        $this->assertArrayHasKey('a', $result['edited']);
        $this->assertArrayHasKey('b', $result['edited']['a']);
    }

    public function testGetdiffReturnsJsonString(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "jsonstring"));

        $result = $treeWalker->getdiff(array('a' => 1), array('a' => 2), false);

        $this->assertIsString($result);
        $this->assertIsArray(json_decode($result, true));
    }

    public function testGetdiffReturnsObject(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "object"));

        $result = $treeWalker->getdiff(array('a' => 1), array('a' => 2), false);

        $this->assertIsObject($result);
        $this->assertObjectHasProperty('edited', $result);
    }

    // -------------------------------------------------------------------------
    // getDynamicallyValue
    // -------------------------------------------------------------------------

    public function testGetDynamicallyValueReturnsScalar(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('a' => array('b' => array('c' => 42)));

        $this->assertEquals(42, $treeWalker->getDynamicallyValue($struct, array('a', 'b', 'c')));
    }

    public function testGetDynamicallyValueReturnsNestedArray(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('a' => array('b' => array('x' => 1, 'y' => 2)));

        $this->assertEquals(array('x' => 1, 'y' => 2), $treeWalker->getDynamicallyValue($struct, array('a', 'b')));
    }

    public function testGetDynamicallyValueWithJsonInput(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $this->assertEquals(99, $treeWalker->getDynamicallyValue('{"a":{"b":99}}', array('a', 'b')));
    }

    // -------------------------------------------------------------------------
    // setDynamicallyValue
    // -------------------------------------------------------------------------

    public function testSetDynamicallyValueUpdatesNestedKey(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('a' => array('b' => 1));
        $result = $treeWalker->setDynamicallyValue($struct, array('a', 'b'), 99);

        $this->assertEquals(array('a' => array('b' => 99)), $result);
    }

    public function testSetDynamicallyValueDoesNotMutateOriginal(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('a' => array('b' => 1));
        $treeWalker->setDynamicallyValue($struct, array('a', 'b'), 99);

        $this->assertEquals(1, $struct['a']['b']);
    }

    // -------------------------------------------------------------------------
    // createDynamicallyObjects
    // -------------------------------------------------------------------------

    public function testCreateDynamicallyObjectsOnEmptyStruct(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $result = $treeWalker->createDynamicallyObjects(array(), array('level1', 'level2'));

        $this->assertEquals(array('level1' => array('level2' => array())), $result);
    }

    public function testCreateDynamicallyObjectsAddsToExistingStruct(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('existing' => 1);
        $result = $treeWalker->createDynamicallyObjects($struct, array('new', 'path'));

        $this->assertArrayHasKey('existing', $result);
        $this->assertEquals(array(), $result['new']['path']);
    }

    // -------------------------------------------------------------------------
    // walker
    // -------------------------------------------------------------------------

    public function testWalkerModifiesValues(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('a' => 1, 'b' => 2, 'c' => array('d' => 3));
        $result = $treeWalker->walker($struct, function (&$struct, $key, &$value) {
            if (is_int($value)) {
                $value = $value * 2;
            }
        });

        $this->assertEquals(array('a' => 2, 'b' => 4, 'c' => array('d' => 6)), $result);
    }

    public function testWalkerDeletesNode(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('a' => 1, 'b' => 2, 'c' => 3);
        $result = $treeWalker->walker($struct, function (&$struct, $key, &$value) {
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
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct1 = array('a' => 1, 'b' => 2);
        $struct2 = array('b' => 99, 'c' => 3);

        $result = $treeWalker->structMerge($struct1, $struct2, false);

        $this->assertEquals(1, $result['a']);
        $this->assertEquals(2, $result['b']);
        $this->assertEquals(3, $result['c']);
    }

    public function testStructMergeSlashToObject(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $result = $treeWalker->structMerge(
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
        $treeWalker = new TreeWalker(array("debug" => true, "returntype" => "array"));

        $result = $treeWalker->getdiff(array('a' => 1), array('a' => 2), false);

        $this->assertArrayHasKey('time', $result);
        $this->assertStringContainsString('milliseconds', $result['time']);
    }

    public function testDebugModeWalker(): void
    {
        $treeWalker = new TreeWalker(array("debug" => true, "returntype" => "array"));

        $result = $treeWalker->walker(array('a' => 1), function (&$struct, $key, &$value) {});

        $this->assertArrayHasKey('time', $result);
        $this->assertStringContainsString('milliseconds', $result['time']);
    }

    public function testDebugModeStructMerge(): void
    {
        $treeWalker = new TreeWalker(array("debug" => true, "returntype" => "array"));

        $result = $treeWalker->structMerge(array('a' => 1), array('b' => 2), false);

        $this->assertArrayHasKey('time', $result);
        $this->assertStringContainsString('milliseconds', $result['time']);
    }

    // -------------------------------------------------------------------------
    // error handling and edge cases
    // -------------------------------------------------------------------------

    public function testStudyTypeReturnsFalseOnInvalidInput(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $result = $treeWalker->getdiff(42, array('a' => 1), false);

        $this->assertEquals("the parameter is not a valid structure", $result);
    }

    public function testInvalidReturntype(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "invalid"));

        $result = $treeWalker->getdiff(array('a' => 1), array('a' => 2), false);

        $this->assertEquals("returntype is not valid!", $result);
    }

    public function testGetDynamicallyValueKeyNotFound(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('a' => array('b' => 1));
        $result = $treeWalker->getDynamicallyValue($struct, array('a', 'nonexistent'));

        $this->assertStringContainsString('error', $result);
    }

    public function testSetDynamicallyValueKeyNotFound(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $struct = array('a' => array('b' => 1));
        $result = $treeWalker->setDynamicallyValue($struct, array('nonexistent', 'b'), 99);

        $this->assertEquals(array('a' => array('b' => 1)), $result);
    }

    public function testGetdiffWithNonEmptyNestedObject(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $obj1 = new stdClass();
        $obj1->x = 1;
        $obj2 = new stdClass();
        $obj2->x = 2;

        $result = $treeWalker->getdiff(array('a' => $obj1), array('a' => $obj2), false);

        $this->assertArrayHasKey('a/x', $result['edited']);
    }

    public function testGetdiffWithEmptyNestedObject(): void
    {
        $treeWalker = new TreeWalker(array("debug" => false, "returntype" => "array"));

        $result = $treeWalker->getdiff(array('a' => new stdClass()), array('a' => new stdClass()), false);

        $this->assertEquals(array(), $result['new']);
        $this->assertEquals(array(), $result['removed']);
        $this->assertEquals(array(), $result['edited']);
    }
}
