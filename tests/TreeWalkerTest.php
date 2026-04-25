<?php

declare(strict_types=1);

use Lukascivil\TreeWalker\TreeWalker;
use PHPUnit\Framework\TestCase;

class TreeWalkerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // getdiff
    // -------------------------------------------------------------------------

    public function testGetdiffSimpleStructs(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct1 = ['1' => ['2' => '7', '3' => ['4' => '6']]];
        $struct2 = ['1' => ['3' => ['4' => '5']]];

        $expected = [
            'edited'  => ['1/3/4' => ['newvalue' => '5', 'oldvalue' => '6']],
            'new'     => [],
            'removed' => ['1/2' => '7'],
        ];

        $this->assertEquals($expected, $tw->getdiff($struct2, $struct1, false));
    }

    public function testGetdiffWithArrayProperty(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct1 = ['a' => 1, 'b' => [['c1' => 1], ['c2' => 2]]];
        $struct2 = ['a' => 11, 'b' => [['c1' => 1], ['c2' => 22]]];

        $expected = [
            'edited'  => [
                'a'     => ['newvalue' => 11, 'oldvalue' => 1],
                'b/1/c2' => ['newvalue' => 22, 'oldvalue' => 2],
            ],
            'new'     => [],
            'removed' => [],
        ];

        $this->assertEquals($expected, $tw->getdiff($struct2, $struct1, false));
    }

    public function testGetdiffWithDifferentTypes(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct1 = ['a' => 1, 'b' => ['c1' => 2], 'c' => 3];
        $struct2 = ['a' => '1', 'b' => 2, 'c' => true];

        $expected = [
            'edited'  => [
                'a' => ['newvalue' => '1', 'oldvalue' => 1],
                'c' => ['newvalue' => true, 'oldvalue' => 3],
            ],
            'new'     => ['b' => 2],
            'removed' => ['b/c1' => 2],
        ];

        $this->assertEquals($expected, $tw->getdiff($struct2, $struct1, false));
    }

    public function testGetdiffSlashToObject(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct1 = ['a' => ['b' => 1]];
        $struct2 = ['a' => ['b' => 2]];

        $result = $tw->getdiff($struct1, $struct2, true);

        $this->assertArrayHasKey('edited', $result);
        $this->assertArrayHasKey('a', $result['edited']);
        $this->assertArrayHasKey('b', $result['edited']['a']);
    }

    public function testGetdiffWithJsonStringInput(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $json1 = '{"a": 1, "b": 2}';
        $json2 = '{"a": 1, "b": 3}';

        $result = $tw->getdiff($json1, $json2, false);

        $this->assertArrayHasKey('edited', $result);
        $this->assertArrayHasKey('b', $result['edited']);
        $this->assertEquals(3, $result['edited']['b']['oldvalue']);
        $this->assertEquals(2, $result['edited']['b']['newvalue']);
    }

    public function testGetdiffWithObjectInput(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct1 = (object) ['a' => 1, 'b' => 2];
        $struct2 = (object) ['a' => 1, 'b' => 3];

        $result = $tw->getdiff($struct1, $struct2, false);

        $this->assertArrayHasKey('edited', $result);
        $this->assertArrayHasKey('b', $result['edited']);
    }

    // -------------------------------------------------------------------------
    // getdiff return types
    // -------------------------------------------------------------------------

    public function testGetdiffReturnsJsonString(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'jsonstring']);

        $result = $tw->getdiff(['a' => 1], ['a' => 2], false);

        $this->assertIsString($result);
        $this->assertIsArray(json_decode($result, true));
    }

    public function testGetdiffReturnsObject(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'object']);

        $result = $tw->getdiff(['a' => 1], ['a' => 2], false);

        $this->assertIsObject($result);
        $this->assertObjectHasProperty('edited', $result);
    }

    // -------------------------------------------------------------------------
    // getDynamicallyValue
    // -------------------------------------------------------------------------

    public function testGetDynamicallyValueReturnsScalar(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct = ['a' => ['b' => ['c' => 42]]];

        $this->assertEquals(42, $tw->getDynamicallyValue($struct, ['a', 'b', 'c']));
    }

    public function testGetDynamicallyValueReturnsNestedArray(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct = ['a' => ['b' => ['x' => 1, 'y' => 2]]];

        $this->assertEquals(['x' => 1, 'y' => 2], $tw->getDynamicallyValue($struct, ['a', 'b']));
    }

    public function testGetDynamicallyValueWithJsonInput(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $this->assertEquals(99, $tw->getDynamicallyValue('{"a": {"b": 99}}', ['a', 'b']));
    }

    public function testGetDynamicallyValueThrowsOnMissingKey(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $this->expectException(\RuntimeException::class);
        $tw->getDynamicallyValue(['a' => ['b' => 1]], ['a', 'missing']);
    }

    // -------------------------------------------------------------------------
    // setDynamicallyValue
    // -------------------------------------------------------------------------

    public function testSetDynamicallyValueUpdatesNestedKey(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct = ['a' => ['b' => 1]];
        $result = $tw->setDynamicallyValue($struct, ['a', 'b'], 99);

        $this->assertEquals(['a' => ['b' => 99]], $result);
    }

    public function testSetDynamicallyValueDoesNotMutateOriginal(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct = ['a' => ['b' => 1]];
        $tw->setDynamicallyValue($struct, ['a', 'b'], 99);

        $this->assertEquals(1, $struct['a']['b']);
    }

    public function testSetDynamicallyValueThrowsOnMissingKey(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $this->expectException(\RuntimeException::class);
        $tw->setDynamicallyValue(['a' => ['b' => 1]], ['a', 'missing', 'deep'], 5);
    }

    // -------------------------------------------------------------------------
    // createDynamicallyObjects
    // -------------------------------------------------------------------------

    public function testCreateDynamicallyObjectsOnEmptyStruct(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $result = $tw->createDynamicallyObjects([], ['level1', 'level2']);

        $this->assertEquals(['level1' => ['level2' => []]], $result);
    }

    public function testCreateDynamicallyObjectsAddsToExistingStruct(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct = ['existing' => 1];
        $result = $tw->createDynamicallyObjects($struct, ['new', 'path']);

        $this->assertArrayHasKey('existing', $result);
        $this->assertArrayHasKey('new', $result);
        $this->assertEquals([], $result['new']['path']);
    }

    // -------------------------------------------------------------------------
    // walker
    // -------------------------------------------------------------------------

    public function testWalkerMultipliesIntValues(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct = ['a' => 1, 'b' => 2, 'c' => ['d' => 3]];
        $result = $tw->walker($struct, function (array &$node, $key, &$value): void {
            if (is_int($value)) {
                $value = $value * 2;
            }
        });

        $this->assertEquals(['a' => 2, 'b' => 4, 'c' => ['d' => 6]], $result);
    }

    public function testWalkerDeletesNode(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct = ['a' => 1, 'b' => 2, 'c' => 3];
        $result = $tw->walker($struct, function (array &$node, $key, &$value): void {
            if ($key === 'b') {
                unset($node[$key]);
            }
        });

        $this->assertEquals(['a' => 1, 'c' => 3], $result);
    }

    public function testWalkerWithJsonInput(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct = '{"x": 10, "y": 20}';
        $result = $tw->walker($struct, function (array &$node, $key, &$value): void {
            if (is_int($value)) {
                $value = 0;
            }
        });

        $this->assertEquals(['x' => 0, 'y' => 0], $result);
    }

    // -------------------------------------------------------------------------
    // structMerge
    // -------------------------------------------------------------------------

    public function testStructMergeFirstOverridesSecond(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct1 = ['a' => 1, 'b' => 2];
        $struct2 = ['b' => 99, 'c' => 3];

        $result = $tw->structMerge($struct1, $struct2, false);

        $this->assertEquals(1, $result['a']);
        $this->assertEquals(2, $result['b']);  // struct1 wins
        $this->assertEquals(3, $result['c']);
    }

    public function testStructMergeFlattensNestedStructs(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct1 = ['a' => ['b' => 1]];
        $struct2 = ['a' => ['c' => 2]];

        $result = $tw->structMerge($struct1, $struct2, false);

        $this->assertArrayHasKey('a/b', $result);
        $this->assertArrayHasKey('a/c', $result);
    }

    public function testStructMergeSlashToObject(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'array']);

        $struct1 = ['a' => ['b' => 1]];
        $struct2 = ['a' => ['c' => 2]];

        $result = $tw->structMerge($struct1, $struct2, true);

        $this->assertArrayHasKey('a', $result);
        $this->assertArrayHasKey('b', $result['a']);
        $this->assertArrayHasKey('c', $result['a']);
    }

    // -------------------------------------------------------------------------
    // debug mode
    // -------------------------------------------------------------------------

    public function testDebugModeAddsTimeKey(): void
    {
        $tw = new TreeWalker(['debug' => true, 'returntype' => 'array']);

        $result = $tw->getdiff(['a' => 1], ['a' => 2], false);

        $this->assertArrayHasKey('time', $result);
        $this->assertStringContainsString('milliseconds', $result['time']);
    }

    // -------------------------------------------------------------------------
    // invalid input
    // -------------------------------------------------------------------------

    public function testInvalidReturnTypeThrows(): void
    {
        $tw = new TreeWalker(['debug' => false, 'returntype' => 'invalid']);

        $this->expectException(\InvalidArgumentException::class);
        $tw->getdiff(['a' => 1], ['a' => 2], false);
    }
}
