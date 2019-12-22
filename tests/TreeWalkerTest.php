<?php
use PHPUnit\Framework\TestCase;

class StackTest extends TestCase
{
    public function testSimpleStructs()
    {
        $treewalker = new TreeWalker(array(
        "debug" => false,
        "returntype" => "array"
        ));
        $struct1 = array(
        '1' => array('2' => '7', '3' => array('4' => '6'))
        );
        $struct2 = array(
        '1' => array('3' => array('4' => '5'))
        );
        $expectedResult = array(
        'edited'=> array('1/3/4'=> array('newvalue'=> '5', 'oldvalue'=> '6' )),
        'new'=> array(),
        'removed'=> array( '1/2'=> '7' )
        );
        $result = $treewalker->getdiff($struct2, $struct1, false);

        $this->assertEquals($result, $expectedResult);
    }

    public function testStructsContainingArrayProperty()
    {
        $treewalker = new TreeWalker(array(
        "debug" => false,
        "returntype" => "array"
        ));
        $struct1 = array(
        'a' => 1,
        'b' => array(array('c1' => 1), array('c2' => 2))
        );
        $struct2 = array(
        'a' => 11,
        'b' => array(array('c1' => 1), array('c2' => 22))
        );
        $expectedResult = array(
        'edited'=> array('a'=> array('newvalue'=> 11, 'oldvalue'=> 1 ), 'b/1/c2'=> array('newvalue'=> 22, 'oldvalue'=> 2 )),
        'new'=> array(),
        'removed'=> array()
        );
        $result = $treewalker->getdiff($struct2, $struct1, false);

        $this->assertEquals($result, $expectedResult);
    }

    public function testStructsContainingDifferentTypes()
    {
        $treewalker = new TreeWalker(array(
        "debug" => false,
        "returntype" => "array"
        ));
        $struct1 = array(
        'a' => 1,
        'b' => array('c1' => 2),
        'c' => 3
        );
        $struct2 = array(
        'a' => '1',
        'b' => 2,
        'c' => true
        );
        $expectedResult = array(
        'edited'=> array('a'=> array('newvalue'=> '1', 'oldvalue'=> 1 ), 'c'=> array('newvalue'=> true, 'oldvalue'=> 3 )),
        'new'=> array('b' => 2),
        'removed'=> array('b/c1' => 2)
        );
        $result = $treewalker->getdiff($struct2, $struct1, false);

        $this->assertEquals($result, $expectedResult);
    }
}
