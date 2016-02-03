# treeWalker

TreeWalker is a simple and smal API in php

  - Get json difference
  - Edit json value (Recursively)

### Version
0.1

### Examples

(Init):

    include "../src/treeWalker.php";

      $treeWalker = new treeWalker(array(
        "debug"=>true,                      //true => return the execution time, false => not
        "returntype"=>"jsonstring")         //Returntype = ["obj","jsonstring","array"]
      );

Methods:
```sh
    //getdiff() - this method will return the diference between json1 and json2
    
    $struct1 = array("casa"=>1, "b"=>"5", "cafeina"=>array("ss"=>"ddd"), "oi"=>5);
    $struct2 = array("casa"=>2, "cafeina"=>array("ss"=>"dddd"), "oi2"=>5);

    //P.s
    print_r($treeWalker->getdiff($struct1, $struct2))

    {
        new: {
            b: "5",
            oi: 5
        },
        removed: {
            oi2: 5
        },
        edited: {
            casa: {
              oldvalue: 2,
              newvalue: 1
            },
            cafeina/ss: {
              oldvalue: "dddd",
              newvalue: "ddd"
            }
        },
        time: 0
    }

```

```sh
    //replaceValues - this method will walk in tree recursively, you can change so values passing the field name
    
    $struct1 = array("casa"=>1, "b"=>"5", "cafeina"=>array("ss"=>"ddd"), "oi"=>5);
    $struct2 = array("casa"=>2, "cafeina"=>array("ss"=>"dddd"), "oi2"=>5);

    //P.s
    print_r($treeWalker->replaceValues($struct2, "test", "ss", false));
    {
        casa: 2,
        cafeina: {
            ss: "test"
        },
        oi2: 5,
        time: 0
    }
```

License
----
The MIT License (MIT)

    Copyright (c) [2016] [LUCAS CORDEIRO DA SILVA]

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
