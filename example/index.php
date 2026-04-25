<?php

require_once __DIR__ . '/../vendor/autoload.php';

$treewalker = new TreeWalker(array(
    "debug" => true,
    "returntype" => "jsonstring"
));

$struct1 = file_get_contents(__DIR__ . '/json/json1.json');
$struct2 = json_decode(file_get_contents(__DIR__ . '/json/json2.json'), true);
$struct3 = array("casa" => 1, "b" => "5", "cafeina" => array("ss1" => "1", "ss2" => "2"), "oi" => 5, "1" => "255");
$struct4 = array("casa" => 2, "cafeina" => array("ss" => array("ff" => 21, "ff1" => 22)), "oi2" => 5, "1" => "", "ss" => "dddddf");

$struct5 = new stdClass();
$struct5->oi = "s55";
$struct5->cafe = "quente";
$struct5->oi1 = "oi1";

class classstruct1
{
    public $cafe = "frio";
}

class classstruct2 extends classstruct1
{
    public $struct4cc = "2";
}

$struct6 = new classstruct1();
$struct7 = new classstruct2();

$struct8 = $struct3;
$struct8["cafeina"]["ss"] = new classstruct2();

// getdiff(modified struct, static struct, slashtostruct)
echo "\ngetdiff(modified struct, static struct, slashtostruct)<br/>\n";
print_r($treewalker->getdiff($struct1, $struct2, true));
echo "<br/><br/>\n\n";

// walker(struct, function)
echo "walker(struct, function)<br/>\n";
print_r($treewalker->walker($struct4, function (&$struct, $key, &$value) {
    if ($key == "ff") {
        unset($struct[$key]);
    }
    if ($key == "ff1") {
        $value = array("son" => "tiago");
    }
}));
echo "<br/><br/>\n\n";

// createDynamicallyObjects(struct, newObjectPath)
echo "createDynamicallyObjects(struct, newObjectPath)<br/>\n";
print_r($treewalker->createDynamicallyObjects($struct3, array("cafeina", "novo")));
echo "<br/><br/>\n\n";

// getDynamicallyValue(struct, path)
echo "getDynamicallyValue(struct, path)<br/>\n";
$dynamicpath = array("cafeina", "ss");
print_r($treewalker->getDynamicallyValue($struct4, $dynamicpath));
echo "<br/>\n\n";

// setDynamicallyValue(struct, path, value)
echo "setDynamicallyValue(struct, path, value)<br/>\n";
$dynamicpath = array("cafeina", "ss");
print_r($treewalker->setDynamicallyValue($struct4, $dynamicpath, "newvalue"));
echo "<br/>\n\n";

// structMerge(struct1, struct2, slashtostruct)
echo "structMerge<br/>\n";
print_r($treewalker->structMerge($struct4, $struct3, true));
echo "<br/>\n\n";
