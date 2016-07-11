<?php

/*
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
*/

class TreeWalker
{

    private $debug = false;
    private $returnType = "jsonstring";

    public function __construct($config)
    {

        $config = array_change_key_case($config, CASE_LOWER);

        if (isset($config["debug"])) {
            $this->debug = $config["debug"];
        }

        if ($config["returntype"]) {
            $this->returnType = strtolower($config["returntype"]);
        }
    }

    /**
     * @param \stdClass|string|array $struct1 Structure
     * @param array                  $keypath_array Array with the keys to access dynamically
     * @return string
     */
    public function getDynamicallyValue($struct1, $keypath_array)
    {
        if (!$this->studytype($struct1, $problem)) {
            return $problem;
        }

        $value = $this->getDynamically($struct1, $keypath_array);
        return $this->returnTypeConvert($value);
    }

    private function getDynamically($struct_assocarray, $keypath_array)
    {
        if (empty($keypath_array)) { // -> Stop Recursion
            return $struct_assocarray;
        }

        $key = array_shift($keypath_array);

        if (is_array($struct_assocarray)) {
            if (array_key_exists($key, $struct_assocarray)) {
                return $this->getDynamically($struct_assocarray[$key], $keypath_array);
            } else {
                return '{"error": "Error, some key does not exist!"}';
            }
        }
    }

    /**
     * Create nested obj Dynamically if(key exist || key !exist), by $path_string
     *
     * @param array|string|\stdClass $struct1 Structure
     * @param array                  $keypath_array Array with the keys that will be created
     * @return array|string|\stdClass
     */
    public function createDynamicallyObjects(&$struct1, $keypath_array)
    {
        if (!$this->studytype($struct1, $problem)) {
            return $problem;
        }

        $path_string = "";

        for ($i = 0; $i < count($keypath_array); $i++) {
            $key = $keypath_array[$i];
            $path_string .= $key . "/";
        }

        $this->accessDynamically($path_string, $struct1);
        $this->returnTypeConvert($struct1);
    }

    /**
     * @param string $path_string path
     * @param array  $array current array
     */
    private function accessDynamically($path_string, &$array)
    {
        $keys = explode('/', substr_replace($path_string, "", -1));
        $ref = &$array;

        while ($key = array_shift($keys)) {
            $ref = &$ref[$key];
        }
        $ref = array();
    }

    public function replaceValues($struct1, $newvalue, $field, $onlyseed)
    {
        if (!$this->studytype($struct1, $problem)) {
            return $problem;
        }
        $time_start = microtime(true);

        $replaced_array = $this->replaceWalker($struct1, $newvalue, $field, $onlyseed);

        if ($this->debug) {
            $time_end = microtime(true);
            $time = $time_end - $time_start;
            $replaced_array["time"] = $time;
        }

        $this->returnTypeConvert($replaced_array);

        return $replaced_array;
    }

    public function getdiff($struct1, $struct2)
    {

        if (!$this->studytype($struct1, $problem) || !$this->studytype($struct2, $problem)) {
            return $problem;
        }

        $time_start = microtime(true);
        $this->structPathArray($struct1, $structpath1_array, "");
        $this->structPathArray($struct2, $structpath2_array, "");
        $this->structPathArrayDiff($structpath1_array, $structpath2_array, $deltadiff_array);

        if ($this->debug) {
            $time_end = microtime(true);
            $time = $time_end - $time_start;
            $deltadiff_array["time"] = $time;
        }

        $this->returnTypeConvert($deltadiff_array);
        return $deltadiff_array;
    }

    private function structPathArray($assocarray, &$array, $currentpath)
    {
        if (is_array($assocarray)) {
            foreach ($assocarray as $key => $value) {
                if (isset($assocarray[$key])) {
                    if ($key != "_id") {
                        //Lógica 1
                        $path = $currentpath ? $currentpath . "/" . $key : $key;
                    } else {
                        $path = $currentpath;
                    }

                    if (gettype($assocarray[$key]) == "array") {
                        $this->structPathArray($assocarray[$key], $array, $path);
                    } else {
                        if ($path != "") {
                            //Lógica 1
                            $array[$path] = $value;
                        }
                    }
                }
            }
        }
    }

    private function replaceWalker(&$assocarray, $newvalue, $field, $onlyseed)
    {
        if (is_array($assocarray)) {
            foreach ($assocarray as $key => &$value) {
                if (isset($assocarray[$key])) {
                    if (is_array($assocarray[$key])) {
                        if (!$onlyseed) {
                            if ($key == $field) {
                                $value = $newvalue;
                            }
                        }
                        $this->replaceWalker($assocarray[$key], $newvalue, $field, $onlyseed);
                    } else {
                        if (isset($newvalue)) {
                            if (isset($field)) {
                                if ($key == $field) {
                                    $value = $newvalue;
                                }
                            } else {
                                $value = $newvalue;
                            }
                        }
                    }
                }
            }
        }
        return $assocarray;
    }

    private function structPathArrayDiff($structpath1_array, $structpath2_array, &$deltadiff_array)
    {

        $deltadiff_array = array(
            "new"     => array(),
            "removed" => array(),
            "edited"  => array()
        );

        foreach ($structpath1_array as $key1 => $value1) {
            if (array_key_exists($key1, $structpath2_array)) {

                if ($value1 != $structpath2_array[$key1]) {

                    $edited = array(
                        "oldvalue" => $structpath2_array[$key1],
                        "newvalue" => $value1
                    );
                    $deltadiff_array["edited"][$key1] = $edited;
                }
            } else {
                $deltadiff_array["new"][$key1] = $value1;
            }
        }

        $removido = array_diff_key($structpath2_array, $structpath1_array);

        /*print_r($structpath2_array);
        echo "----------------------";
        print_r($structpath1_array);*/

        if (!empty($removido)) {
            foreach ($removido as $key => $value) {
                $deltadiff_array["removed"][$key] = $value;
            }
        }

        //print_r($deltadiff_array);
    }

    private function returnTypeConvert(&$struct1)
    {
        switch ($this->returnType) {

            case 'jsonstring':
                $struct1 = json_encode($struct1);
                break;
            case 'obj':
                $struct1 = json_decode(json_encode($struct1), false);
                break;
            case 'array':
                break;
            default:
                return "returntype não é valido!";
                break;
        }
    }

    private function studytype(&$struct1, &$problem)
    {
        if ($this->isJsonString($struct1)) {
            $struct1 = json_decode($struct1, true);
            return true;
        } else {
            if (is_array($struct1)) {
                return true;
            } else {
                if (is_object($struct1)) {
                    return true;
                } else {
                    $problem = "comptype não é válido";
                    return false;
                }
            }
        }
    }

    private function isJsonString($string)
    {
        if (!is_string($string)) {
            return false;
        } else {
            return (json_last_error() == JSON_ERROR_NONE);
        }
    }
}
