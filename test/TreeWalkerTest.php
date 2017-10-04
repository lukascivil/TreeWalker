<?php

use PHPUnit\Framework\TestCase;

class TreeWalkerTest extends TestCase
{
    /**
     * @test
     */
    public function testGetDiff()
    {

        // test an empty header stdClass
        $expectedJson = '{"method":"POST","path":"/","query":"","headers":{},"body":{"alligator":{"name":"Mary","feet":4,"favouriteColours":["red","blue"]}}}';
        $expected = \json_decode($expectedJson);

        $actualJson = '{"method":"POST","path":"/","query":"","headers":{},"body":{"alligator":{"feet":4,"name":"Mary","favouriteColours":["red","blue"]}}}';
        $actual = \json_decode($actualJson);

        $treewalker = new \TreeWalker(array(
            "debug" => false,                     //true => return the execution time, false => not
            "returntype" => "array")              //Returntype = ["obj","jsonstring","array"]
        );

        $results = $treewalker->getdiff($expected, $actual, false);

        $this->assertEquals(0, count($results['edited']), "Expect that there are no differences.   This is to explicitly test the empty header");
    }

}
