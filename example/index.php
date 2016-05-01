<?php
    include "../src/treeWalker.php";

    $treeWalker = new treeWalker(array(
        "debug"=>true, //true => return the time, false => not
        "returntype"=>"jsonstring") //Returntype = ["obj","jsonstring","array"]
    );

    /*$struct2 = json_decode(utf8_encode(file_get_contents('json/json1.json')), true);
    $struct1 = json_decode(utf8_encode(file_get_contents('json/json2.json')), true);*/

    $struct1 = array("casa"=>1, "b"=>"5", "cafeina"=>array("ss"=>"ddd"), "oi"=>5, "1" => "255");
    //$struct2 = array("casa"=>2, "cafeina"=>array("ss"=>"dddd"), "oi2"=>5);

    //first argument == modified, second argument static
    //print_r($treeWalker->getdiff($struct1, $struct2));
    //print_r($treeWalker->replaceValues($struct2, "test", "ss", false));

    $treeWalker->createDynamicallyObjects($struct1, array(1,2,5,9,10,11));
    print_r($struct1);
?>
