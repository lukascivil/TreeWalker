# TreeWalker

TreeWalker is a simple and small Library that will help you to work faster with manipulation of structures in PHP

[![Build Status](https://github.com/lukascivil/TreeWalker/workflows/PHP%20Composer/badge.svg)]()
[![Total Downloads](https://poser.pugx.org/lukascivil/treewalker/downloads)](https://packagist.org/packages/lukascivil/treewalker)
[![codecov](https://codecov.io/gh/lukascivil/TreeWalker/branch/master/graph/badge.svg)](https://codecov.io/gh/lukascivil/TreeWalker)
[![License](https://poser.pugx.org/lukascivil/treewalker/license.svg)](https://packagist.org/packages/lukascivil/treewalker)

- getdiff() - Get json difference
- ~~replaceValues() - Edit json value (Recursively)~~
- walker() - Edit json (Recursively)
- structMerge() - Joins two structures
- createDynamicallyObjects() - Create nested structure by Dynamic keys
- getDynamicallyValue() - Dynamically get a structure property
- setDynamicallyValue() - Dynamically access a structure property to set a value

_structure = ["jsonstring", "object", "array"]_

### [EXAMPLE - master](http://treewalker.lukascivil.com.br/)

### Prerequisites

- PHP >= 5.5

## Installation

### Using composer

Put the require statement for `TreeWalker` in your `composer.json` and install:

```json
{
  "require": {
    "lukascivil/treewalker": "dev-master"
  }
}
```

```
composer require lukascivil/treewalker dev-master
```

### Manually

include the `TreeWalker.php`

```php
<?php
include 'pathto/TreeWalker.php';
```

### Examples

Init:

      $treewalker = new TreeWalker(array(
        "debug"=>true,                      //true => return the execution time, false => not
        "returntype"=>"jsonstring")         //Returntype = ["obj","jsonstring","array"]
      );

Methods:

```sh
    //getdiff() - this method will return the diference between struct1 and struct2

    $struct1 = array("casa"=>1, "b"=>"5", "cafeina"=>array("ss"=>"ddd"), "oi"=>5);
    $struct2 = array("casa"=>2, "cafeina"=>array("ss"=>"dddd"), "oi2"=>5);

    $treewalker->getdiff($struct1, $struct2, false) // false -> with slashs

    Output:
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
    //walker() - Walk recursively through the structure

    $struct = array("casa"=>2, "cafeina"=>array("ss"=>array("ff"=>21, "ff1"=>22)), "oi2"=>5, "1"=>"", "ss"=>"dddddf");

    $treewalker->walker($struct, function(&$struct, $key, &$value) {
        //Removing element
        if ($key == "ff") {
            unset($struct[$key]);
        }

        //changing element
        if ($key == "ff1") {
            $value = array("son" => "tiago");
        }
    })

    Output:
    {"casa":2,"cafeina":{"ss":{"ff1":{"son":"tiago"}}},"oi2":5,"1":"","ss":"dddddf","time":"0 miliseconds"}

```

```sh
    //structMerge() - Merge Structures

    $struct1 = array("casa"=>1, "b"=>"5", "cafeina"=>array("ss1"=>"1", "ss2"=>"2"), "oi"=>5, "1" => "255");
    $struct2 = array("casa"=>2, "cafeina"=>array("ss"=>array("ff"=>21, "ff1"=>22)), "oi2"=>5, "1"=>"", "ss"=>"dddddf");

    $treewalker->structMerge($struct2, $struct1, true); //true -> No slashs

    Output:
    {"casa":2,"b":"5","cafeina":{"ss1":"1","ss2":"2","ss":{"ff":21,"ff1":22}},"oi":5,"0":"255","oi2":5,"1":"","ss":"dddddf","time":"0 miliseconds"}
```

```sh
    //createDynamicallyObjects() - this method will create nested objects with with dynamic keys

    $struct = array("casa"=>1, "b"=>"5", "cafeina"=>array("ss"=>"ddd"), "oi"=>5, "1" => "255");

    //P.s
    $treewalker->createDynamicallyObjects($struct, array(1,2,5,9,10,11));

    Output:

     {
       "casa": 1,
       "b": "5",
       "cafeina": {
          "ss": "ddd"
       },
       "oi": 5,
       "1": {
          "2": {
            "5": {
              "9": {
                "10": {
                  "11": {}
                }
              }
            }
          }
        }
      }
```

```sh
    //getDynamicallyValue()

    $struct = array("casa"=>2, "cafeina"=>array("ss"=>array("ff"=>21, "ff1"=>22)), "oi2"=>5, "1"=>"", "ss"=>"dddddf");

    Static access:
    $struct["cafeina"]["ss"];

    Dynamic access:
    $treewalker->getDynamicallyValue($struct, array("cafeina","ss"));

    Output:
    {"ff":21,"ff1":22}
```

```sh
    //setDynamicallyValue()

    $struct = array("casa"=>2, "cafeina"=>array("ss"=>array("ff"=>21, "ff1"=>22)), "oi2"=>5, "1"=>"", "ss"=>"dddddf");

    Static access:
    $struct["cafeina"]["ss"] = "newvalue";

    Dynamic access:
    $treewalker->setDynamicallyValue($struct, array("cafeina","ss"), "newvalue");

    Output:
    {"casa":2,"cafeina":{"ss":"newvalue"},"oi2":5,"1":"","ss":"dddddf"}
```

## Test

```
composer install
composer test
```

## License

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
