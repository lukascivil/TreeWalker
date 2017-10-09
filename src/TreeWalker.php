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

    private $typetowork = "array";
    private $time_start = 0;
    private $time_end = 0;

    /**
     * [__construct Class]
     * 
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
     * @return [\stdClass|string|array]
     */
    public function getDynamicallyValue($struct, $keypath_array)
    {
        if (!$this->studyType($struct, $problem)) {
            return $problem;
        }

        $getDynamically = function($struct_assocarray, $keypath_array) use (&$getDynamically) {
            
            if (empty($keypath_array)) { // -> Stop Recursion
                return $struct_assocarray;
            }
        
            $key = array_shift($keypath_array);
            
            if (is_array($struct_assocarray)) {

                if (array_key_exists($key, $struct_assocarray)) {
                    return $getDynamically($struct_assocarray[$key], $keypath_array);
                } else {
                    echo "error";
                    return '{"error": "Error, some key does not exist!"}';
                }
            }
        };

        $value = $getDynamically($struct, $keypath_array);

        return $this->returnTypeConvert($value);
    }

    /**
     * @param  [\stdClass|string|array] $struct         [Structure]
     * @param  [array]                  $keypath_array  [Array with the keys to access dynamically]
     * @param  [boolean]                $value          [Simple path with slashs or nested structures]
     * @return [\stdClass|string|array]
     */
    public function setDynamicallyValue($struct, $keypath_array, $value = "") 
    {
        if (!$this->studyType($struct, $problem)) {
            return $problem;
        }

        $setDynamically = function( &$struct_assocarray, $keypath_array, $value ) use (&$setDynamically) {
            if (sizeof($keypath_array) == 1) { // -> Stop Recursion
                $struct_assocarray[$keypath_array[0]] = $value;
            }

            $key = array_shift($keypath_array);

            if (is_array($struct_assocarray)) {
                
                if (array_key_exists($key, $struct_assocarray)) {
                    //echo sizeof($keypath_array);
                    return $setDynamically($struct_assocarray[$key], $keypath_array, $value);

                } else {
                    return '{"error": "Error, some key does not exist!"}';
                }
            }
        };

        $setDynamically($struct, $keypath_array, $value);

        return $this->returnTypeConvert($struct);
    }

    /**
     * [Create nested obj Dynamically if(key exist || key !exist), by $path_string
     * 
     * @param [array|string|\stdClass]  $struct1       [Structure]
     * @param [array]                   $keypath_array [Array with the keys that will be created]
     * @return [array|string|\stdClass]                New structure
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

        $this->accessDynamically($path_string, $struct); // cria
        return $this->returnTypeConvert($struct);
    }

    /**
     * [This function enables you to dynamically access the value of a structure]
     * 
     * @param [string] $path_string   [path]
     * @param [array]  $array current [array]
     */
    private function accessDynamically($path_string, &$array)
    {
        $keys = explode('/', substr_replace($path_string, "", -1));
        $ref = &$array;
        
        foreach ($keys as $key => $value) {
            $ref = &$ref[$value];
        }
        $ref = array();
    }

    /**
     * [walk throughout the structure]
     * 
     * @param  [array|string|\stdClass]         $struct   [Structure]
     * @param  [function]                       $callback [The function will always be called when walking through the nodes]
     * @return [array|string|\stdClass]                   Input structure
     */
    public function walker(&$struct, $callback)
    {
        if (!$this->studyType($struct, $problem)) {
            return $problem;
        }
        
        $this->clockStart();

        $replaceWalker = function(&$struct, $callback) use (&$replaceWalker) {

            if (is_array($struct)) {
                foreach ($struct as $key => &$value) {

                    $callback($struct, $key, $value);

                    if (isset($struct[$key])) {
                        if (is_array($struct[$key])) {
                            $replaceWalker($value, $callback);
                        } else {

                        }
                    }
                }
            }
            return $struct;
        };

        // Call the recursive method to walk in the struct
        $replaced_array = $replaceWalker($struct, $callback);

        if ($this->config["debug"]) {
            $replaced_array["time"] = $this->clockMark();
        }

        return $this->returnTypeConvert($replaced_array);
    }

    /**
     * Returns the difference between two structures
     * 
     * @param  [array|string|\stdClass] $struct1 [struct1]
     * @param  [array|string|\stdClass] $struct2 [struct2]
     * @return [array|string|\stdClass] struct diff
     */
    public function getdiff($struct1, $struct2, $slashtoobject = false)
    {
        if (!$this->studyType($struct1, $problem) || !$this->studyType($struct2, $problem)) {
            return $problem;
        }

        $this->clockStart();

        $structpath1_array = array();
        $structpath2_array = array();

        $this->structPathArray($struct1, $structpath1_array, "");
        $this->structPathArray($struct2, $structpath2_array, "");
        $deltadiff_array = $this->structPathArrayDiff($structpath1_array, $structpath2_array, $slashtoobject);

        if ($this->config["debug"]) {
            $deltadiff_array["time"] = $this->clockMark();
        }

        return $this->returnTypeConvert($deltadiff_array);
    }

    /**
     * [Returns a array with all the possible paths of the structure -> &$array.
     *  There is several ways to do this and i believe it's not the best way,
     *  but I chose this way, separately(structPathArray() + structPathArrayDiff()), 
     *  to be more didactic.
     *  In the middle of recursion I could already make comparisons could be faster.]
     *  
     * @param  [array]  $assocarray  [Structure already standardized as associative array to get performance*]
     * @param  [array]  &$array      [Array paths created from the structure]
     * @param  [string] $currentpath [Current path]
     */
    private function structPathArray($assocarray, &$array, $currentpath)
    {
        if (is_array($assocarray)) {
            foreach ($assocarray as $key => $value) {
                if (array_key_exists($key, $assocarray)) {

                    $path = $currentpath !== '' ? $currentpath . "/" . $key : sprintf($key);

                    if (gettype($assocarray[$key]) == "array" && !empty($assocarray[$key])) {
                        $this->structPathArray($assocarray[$key], $array, $path);
                    } elseif (gettype($assocarray[$key]) == "object") {
                        if (!empty((array)$assocarray[$key]) ) { // Force Casting (array)Obj
                            $this->structPathArray((array)$assocarray[$key], $array, $path);
                        } else {
                            $array[$path] = array();
                        }
                    } else {
                        if ($path != "") {
                            $array[$path] = $value;
                        }
                    }
                }
            }
        }
    }

    /**
     * [Join two structures]
     * 
     * @param  [array|string|\stdClass]   $struct1 [Structure]
     * @param  [array|string|\stdClass]   $struct2 [Structure]
     * @return [array|string|\stdClass]            [The union of structures]
     */
    public function structMerge($struct1, $struct2, $slashtoobject = false) {

        if (!$this->studyType($struct1, $problem) || !$this->studyType($struct2, $problem)) {
            return $problem;
        }

        $this->clockStart();

        $structpath1_array = array();
        $structpath2_array = array();

        $this->structPathArray($struct1, $structpath1_array, "");
        $this->structPathArray($struct2, $structpath2_array, "");
        $merged_array = array_merge($structpath2_array, $structpath1_array);
        
        if ($this->config["debug"]) {
            $merged_array["time"] = $this->clockMark();
        }

        if ($slashtoobject) {
            $merged_array = $this->pathSlashToStruct($merged_array);
        }

        return $this->returnTypeConvert($merged_array);
    }

    /**
     * [Convert the slashs to nested structures]
     * 
     * @param  [type]   $assocarray [Associative array to convert the keys]
     * @return [array]              [No slashs on key]
     */
    private function pathSlashToStruct($assocarray) {
        $new_assocarray = [];

        $this->switchType();

        if (is_array($assocarray)) {
            foreach ($assocarray as $key => $value) {
                if (strpos($key, '/') !== false) {
                    $aux = explode("/", $key);
                    $newkey = $aux[0];
                    array_shift($aux);

                    if (isset($new_assocarray[$newkey])) {
                        $new_assocarray[$newkey] = $this->createDynamicallyObjects($new_assocarray[$newkey], $aux);
                        $new_assocarray[$newkey] = $this->setDynamicallyValue($new_assocarray[$newkey], $aux, $value);
                    } else {
                        $new_assocarray[$newkey] = $this->createDynamicallyObjects(array(), $aux);
                        $new_assocarray[$newkey] = $this->setDynamicallyValue($new_assocarray[$newkey], $aux, $value);
                    } 
                } else {
                    $new_assocarray[$key] = $value;
                }
            }
        }

        $this->switchType();

        return $new_assocarray;
    }

    /**
     * [Returns a structure with the news]
     * 
     * @param  [array]   $structpath1_array [Vector paths of structure 1]
     * @param  [array]   $structpath2_array [Vector paths of structure 2]
     * @param  [boolean] $slashtoobject     [Simple path with slashs or nested structures]
     * @return [array]                      [Delta array]
     */
    private function structPathArrayDiff($structpath1_array, $structpath2_array, $slashtoobject)
    {
        $deltadiff_array = array(
            "new"     => array(),
            "removed" => array(),
            "edited"  => array()
        );

        foreach ($structpath1_array as $key1 => $value1) {
            if (array_key_exists($key1, $structpath2_array)) {

                if ($value1 !== $structpath2_array[$key1]) {

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

        if ($slashtoobject) {
            foreach ($deltadiff_array as $key => &$value) { //the length will be always 3, [new, removed, edited]
                $value = $this->pathSlashToStruct($value);
            }
        } 

        return $deltadiff_array;
    }

    /**
     * [Returns a converted structure]
     * 
     * @param  [array|string|\stdClass] $struct [Structure for converting]
     * @return [array|string|\stdClass]         [Converted structure]
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
     * 
     * @param  [array|string|\stdClass] &$struct  [Structure]
     * @param  [string]                 &$problem [If there is a problem with the structure , it will be returned]
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
     * 
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
     */
    private function clockStart()
    {
        $this->time_start = round(microtime(true) * 1000);
    }

    /**
     * [Marks the current time]
     * 
     * @return [String] Time in milliseconds
     */
    private function clockMark() 
    {
        return round(microtime(true) * 1000) - $this->time_start . " miliseconds";
    }

    /**
     * [switch the type]
     */
    private function switchType() 
    {
        $aux = $this->config["returntype"];
        $this->config["returntype"] = $this->typetowork;
        $this->typetowork = $aux;
    }
}
