<?php
    include "../src/TreeWalker.php";

    $treewalker = new TreeWalker(array(
        "debug" => true, //true => return the time, false => not
        "returntype" => "jsonstring" //Returntype = ["object","jsonstring","array"]
    ));

    $struct1 = utf8_encode(file_get_contents('json/json1.json'));
    $struct2 = json_decode(utf8_encode(file_get_contents('json/json2.json')), true);
    $struct3 = array("casa"=>1, "b"=>"5", "cafeina"=>array("ss"=>"ddd"), "oi"=>5, "1" => "255");
    $struct4 = array("casa"=>2, "cafeina"=>array("ss"=>array("ff"=>22)), "oi2"=>5, "1"=>"", "ss"=>"dddddf");

    $struct5 = new stdClass();
    $struct5->oi = "s55";
    $struct5->cafe = "quente";
    $struct5->oi1 = "oi1";

    class classstruct1 {
        public $cafe = "frio";
    }

    class classstruct2 extends classstruct1{
        public $struct4cc = "2";
    }

    $struct6 = new classstruct1();
    $struct7 = new classstruct2();

    $struct8 = $struct3;
    $struct8["cafeina"]["ss"] = new classstruct2();

    //getdiff(modified struct, static struct)
    echo "\ngetdiff(modified struct, static struct)<br/>\n";
    print_r($treewalker->getdiff($struct4, $struct3));
    echo "<br/><br/>\n\n";

    //replaceValues(struct, newvalue, known key, (boolean)change all the keys values found if the occurrence be a leaf)
    /**
     * {"cafeina": "oi"} -> leaf
     * {"cafeina": "child": {"name": "Lucas"}} -> non-leaf
     */
    echo "replaceValues(struct, newvalue, known key, isleaf)<br/>\n";
    print_r($treewalker->replaceValues($struct4, "test", "ss", false));
    echo "<br/><br/>\n\n";

    //createDynamicallyObjects(struct, newObjectPath)
    echo "createDynamicallyObjects(struct, newObjectPath)<br/>\n";
    print_r($treewalker->createDynamicallyObjects($struct5, array("dd", 2, 5, 9, 10, 11)));
    echo "<br/><br/>\n\n";

    //getDynamicallyValue(struct, static)
    echo "getDynamicallyValue(struct, static)<br/>\n";

    echo "Static access<br/>\n";
    print_r($struct4["cafeina"]["ss"]); // Static access

    echo "\n<br/>Dynamic access<br/>\n";
    $dynamicpath = array("cafeina","ss");
    print_r($treewalker->getDynamicallyValue($struct4, $dynamicpath)); // Dynamic access
    echo "<br/>\n\n";
?>
