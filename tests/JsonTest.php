<?php
use PHPUnit\Framework\TestCase;
include __DIR__ . "/../src/TreeWalker.php";

class StackTest extends TestCase
{
  public function testPushAndPop()
  {
    $treewalker = new TreeWalker(array(
        "debug" => true,
        "returntype" => "array"
    ));

    $struct1 = array(
      '1' => array('2' => '7', '3' => array('4' => '6'))
    );
    $struct2 = array(
      '1' => array('3' => array('4' => '5'))
    );
    $expectedResult = array(
      'edited'=> array(array('1/3/4'=> array('newValue'=> 5), array('oldValue'=> 6 ))), 
      'new'=> null, 
      'removed'=> array( '1/2'=> 7 )
    );
    // print_r($treewalker->getdiff($struct1, $struct2, true));
    $result = $treewalker->getdiff($struct1, $struct2, true);

    $this->assertSame($result, $expectedResult);

    // $stack = [];
    // $this->assertSame(2, count($stack));

    // array_push($stack, 'foo');
    // $this->assertSame('foo', $stack[count($stack)-1]);
    // $this->assertSame(1, count($stack));

    // $this->assertSame('foo', array_pop($stack));
    // $this->assertSame(0, count($stack));
  }
}