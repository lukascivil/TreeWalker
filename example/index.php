<?php
    include "../src/TreeWalker.php";

    $treewalker = new TreeWalker(array(
        "debug" => true, //true => return the time, false => not
        "returntype" => "jsonstring" //Returntype = ["object","jsonstring","array"]
    ));

    $struct1 = utf8_encode(file_get_contents('json/json1.json'));
    $struct2 = json_decode(utf8_encode(file_get_contents('json/json2.json')), true);
    $struct3 = array("casa"=>1, "b"=>"5", "cafeina"=>array("ss1"=>"1", "ss2"=>"2"), "oi"=>5, "1" => "255");
    $struct4 = array("casa"=>2, "cafeina"=>array("ss"=>array("ff"=>21, "ff1"=>22)), "oi2"=>5, "1"=>"", "ss"=>"dddddf");

    $struct5 = new stdClass();
    $struct5->oi = "s55";
    $struct5->cafe = "quente";
    $struct5->oi1 = "oi1";

    class classstruct1 {
        public $cafe = "frio";
    }

    class classstruct2 extends classstruct1 {
        public $struct4cc = "2";
    }

    $struct6 = new classstruct1();
    $struct7 = new classstruct2();

    $struct8 = $struct3;
    $struct8["cafeina"]["ss"] = new classstruct2();

    //getdiff(modified struct, static struct, slashtostruct) -------------------------------
    //slashtostruct can be true or false
    echo "\ngetdiff(modified struct, static struct, slashtostruct)<br/>\n";
    print_r($treewalker->getdiff($struct1, $struct2, true));
    echo "<br/><br/>\n\n";

    //walker(struct, function) -------------------------------------------------------------
    echo "walker(struct, function)<br/>\n";
    print_r($treewalker->walker($struct4, function(&$struct, $key, &$value) {
        //Removing element
        if ($key == "ff") {
            unset($struct[$key]);
        }

        //changing element
        if ($key == "ff1") {
            $value = array("son" => "tiago");
        }
    }));
    echo "<br/><br/>\n\n";

    //createDynamicallyObjects(struct, newObjectPath) ---------------------------------------
    echo "createDynamicallyObjects(struct, newObjectPath)<br/>\n";
    print_r($treewalker->createDynamicallyObjects($struct3, array("cafeina", "novo")));
    echo "<br/><br/>\n\n";

    //getDynamicallyValue(struct, static) ---------------------------------------------------
    echo "getDynamicallyValue(struct, static)<br/>\n";

    echo "Static access<br/>\n";
    print_r($struct4["cafeina"]["ss"]); // Static access

    echo "\n<br/>Dynamic access<br/>\n";
    $dynamicpath = array("cafeina","ss");
    print_r($treewalker->getDynamicallyValue($struct4, $dynamicpath)); // Dynamic access
    echo "<br/>\n\n";

    //setDynamicallyValue(struct, static, value) --------------------------------------------
    echo "setDynamicallyValue(struct, static, value)<br/>\n";

    echo "Static access<br/>\n";
    $struct4["cafeina"]["ss"] = "newvalue";// Static access
    print_r($struct4);

    $struct4["cafeina"]["ss"] = "";

    echo "\n<br/>Dynamic access<br/>\n";
    $dynamicpath = array("cafeina","ss");
    print_r($treewalker->setDynamicallyValue($struct4, $dynamicpath, "newvalue")); // Dynamic access
    echo "<br/>\n\n";

    //structMerge(struct, static, slashtostruct) --------------------------------------------
    echo "\n<br/>structMerge<br/>\n";
    print_r($treewalker->structMerge($struct4, $struct3, true));
    echo "<br/>\n\n";
?>
