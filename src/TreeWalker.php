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
    private $config = array(
        "debug" => false,
        "returntype" => "jsonstring"
    );

    private $time_start = 0;
    private $time_end = 0;

    /**
     * [__construct Class]
     * @param [array] $config [associative array configuration]
     */
    public function __construct($config)
    {
        $config = array_change_key_case($config, CASE_LOWER);

        $this->config = array_merge($this->config, $config);
    }

    /**
     * @param  [\stdClass|string|array] $struct         [Structure]
     * @param  [array]                  $keypath_array  [Array with the keys to access dynamically]
     * @return [string]
     */
    public function getDynamicallyValue($struct, $keypath_array)
    {
        if (!$this->studyType($struct, $problem)) {
            return $problem;
        }

        $value = $this->getDynamically($struct, $keypath_array);
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
    public function createDynamicallyObjects($struct, $keypath_array)
    {
        if (!$this->studyType($struct, $problem)) {
            return $problem;
        }

        $path_string = "";

        for ($i = 0; $i < count($keypath_array); $i++) {
            $key = $keypath_array[$i];
            $path_string .= $key . "/";
        }

        $this->accessDynamically($path_string, $struct);
        return $this->returnTypeConvert($struct);
    }

    /**
     * This function enables you to dynamically access the value of a structure
     * @param [string] $path_string   [path]
     * @param [array]  $array current [array]
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

    /**
     * changes the value of a node of a structure from a key passed as a parameter . This node may be a leaf or not.
     * @param  [array|string|\stdClass]         $struct  [structure]
     * @param  [array|string|boolean|int|float] $newvalue [new value to replace]
     * @param  [int|string]                     $field    [key]
     * @param  [boolean]                        $onlyleaf [leaf or not]
     * @return [array|string|\stdClass]                   [description]
     */
    public function replaceValues($struct, $newvalue, $field, $onlyleaf)
    {
        if (!$this->studyType($struct, $problem)) {
            return $problem;
        }
        
        $this->clockStart();

        $replaced_array = $this->replaceWalker($struct, $newvalue, $field, $onlyleaf);

        if ($this->config["debug"]) {
            $replaced_array["time"] = $this->clockMark();
        }

        return $this->returnTypeConvert($replaced_array);
    }

    /**
     * Returns the difference between two structures
     * @param  [array|string|\stdClass] $struct1 [struct1]
     * @param  [array|string|\stdClass] $struct2 [struct2]
     * @return [array|string|\stdClass] struct diff
     */
    public function getdiff($struct1, $struct2)
    {
        if (!$this->studyType($struct1, $problem) || !$this->studyType($struct2, $problem)) {
            return $problem;
        }

        $this->clockStart();

        $structpath1_array = array();
        $structpath2_array = array();

        $this->structPathArray($struct1, $structpath1_array, "");
        $this->structPathArray($struct2, $structpath2_array, "");
        $deltadiff_array = $this->structPathArrayDiff($structpath1_array, $structpath2_array);

        if ($this->config["debug"]) {
            $deltadiff_array["time"] = $this->clockMark();
        }

        return $this->returnTypeConvert($deltadiff_array);
    }

    /**
     * [There is several ways to do this and i believe it's not the best way,
     *  but I chose this way, separately(structPathArray() + structPathArrayDiff()), 
     *  to be more didactic.
     *  In the middle of recursion I could already make comparisons could be faster.]
     * @param  [array]  $assocarray  [Structure already standardized as associative array to get performance*]
     * @param  [array]  &$array      [Array paths created from the structure]
     * @param  [string] $currentpath [current path]
     */
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
                    }elseif (gettype($assocarray[$key]) == "object") {
                        $this->structPathArray((array)$assocarray[$key], $array, $path);
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

    /**
     * [its necessary to walk in the object recursively to change the values]
     * @param  [array]                    &$assocarray [structure]
     * @param  [int|float|boolean|string] $newvalue    [new value to replace]
     * @param  [string|int|boolean]       $field       [key]
     * @param  [boolean]                  $onlyleaf    [leaf or not]
     * @return [array]                                 [Structure whith changed value]
     */
    private function replaceWalker(&$assocarray, $newvalue, $field, $onlyleaf)
    {
        if (is_array($assocarray)) {
            foreach ($assocarray as $key => &$value) {
                if (isset($assocarray[$key])) {
                    if (is_array($assocarray[$key])) {
                        if (!$onlyleaf) {
                            if ($key == $field) {
                                $value = $newvalue;
                            }
                        }
                        $this->replaceWalker($assocarray[$key], $newvalue, $field, $onlyleaf);
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

    /**
     * [Here , the object is mounted with the news]
     * @param  [array] $structpath1_array [Vector paths of structure 1]
     * @param  [array] $structpath2_array [Vector paths of structure 2]
     * @return [array]                    [delta array]
     */
    private function structPathArrayDiff($structpath1_array, $structpath2_array)
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

        if (!empty($removido)) {
            foreach ($removido as $key => $value) {
                $deltadiff_array["removed"][$key] = $value;
            }
        }

        return $deltadiff_array;
    }

    /**
     * [Returns a converted structure]
     * @param  [array|string|\stdClass] $struct [structure for converting]
     * @return [array|string|\stdClass]         [converted structure]
     */
    private function returnTypeConvert($struct)
    {
        switch ($this->config["returntype"]) {
            case 'jsonstring':
                if (!($this->isJsonString($struct))) {
                    return json_encode($struct);
                }
                return $struct;
                break;
            case 'object':
                return json_decode(json_encode($struct), false);
                break;
            case 'array':
                return $struct;
                break;
            default:
                return "returntype não é valido!";
                break;
        }
    }

    /**
     * [analyzes the structure]
     * @param  [array|string|\stdClass] &$struct  [structure]
     * @param  [string]                 &$problem [if there is a problem with the structure , it will be returned]
     * @return [type]                             [true->Everything is ok, false->Error]
     */
    private function studyType(&$struct, &$problem)
    {
        if ($this->isJsonString($struct)) {
            $struct = json_decode($struct, true);
            return true;
        } else if(is_array($struct)) {
            return true;
        } else if(is_object($struct)) {
            $struct = (array)$struct;
            return true;
        } else {
            $problem = "the parameter is not a valid structure";
            return false;
        }
    }

    /**
     * [checks if the string is a valid json]
     * @param  [string]    $string [Json string?]
     * @return boolean             [true->Everything is ok, false->Error]
     */
    private function isJsonString($string)
    {
        if (!is_string($string)) {
            return false;
        } else {
            return (json_last_error() == JSON_ERROR_NONE);
        }
    }

    /**
     * [Starts the clock]
     * @return [type] [description]
     */
    private function clockStart()
    {
        $this->time_start = round(microtime(true) * 1000);
    }

    /**
     * [Marks the current time]
     * @return [type] [description]
     */
    private function clockMark() 
    {
        return round(microtime(true) * 1000) - $this->time_start . " miliseconds";
    }
}
