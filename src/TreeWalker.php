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
     * @param array $config
     */
    public function __construct($config)
    {
        $config = array_change_key_case($config, CASE_LOWER);

        $this->config = array_merge($this->config, $config);
    }

    /**
     * @param  \stdClass|string|array $struct
     * @param  array                  $keypath_array
     * @return \stdClass|string|array
     */
    public function getDynamicallyValue($struct, $keypath_array)
    {
        if (!$this->studyType($struct, $problem)) {
            return $problem;
        }

        $getDynamically = function ($struct_assocarray, $keypath_array) use (&$getDynamically) {
            if (empty($keypath_array)) {
                return $struct_assocarray;
            }

            $key = array_shift($keypath_array);

            if (is_array($struct_assocarray)) {
                if (array_key_exists($key, $struct_assocarray)) {
                    return $getDynamically($struct_assocarray[$key], $keypath_array);
                } else {
                    return '{"error": "Error, some key does not exist!"}';
                }
            }
        };

        $value = $getDynamically($struct, $keypath_array);

        return $this->returnTypeConvert($value);
    }

    /**
     * @param  \stdClass|string|array $struct
     * @param  array                  $keypath_array
     * @param  mixed                  $value
     * @return \stdClass|string|array
     */
    public function setDynamicallyValue($struct, $keypath_array, $value = "")
    {
        if (!$this->studyType($struct, $problem)) {
            return $problem;
        }

        $setDynamically = function (&$struct_assocarray, $keypath_array, $value) use (&$setDynamically) {
            if (count($keypath_array) === 1) {
                $struct_assocarray[$keypath_array[0]] = $value;
                return;
            }

            $key = array_shift($keypath_array);

            if (is_array($struct_assocarray)) {
                if (array_key_exists($key, $struct_assocarray)) {
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
     * @param  array|string|\stdClass $struct
     * @param  array                  $keypath_array
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
     * @param string $path_string
     * @param array  $array
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
     * @param  array|string|\stdClass $struct
     * @param  callable               $callback
     * @return array|string|\stdClass
     */
    public function walker(&$struct, $callback)
    {
        if (!$this->studyType($struct, $problem)) {
            return $problem;
        }

        $this->clockStart();

        $replaceWalker = function (&$struct, $callback) use (&$replaceWalker) {

            if (is_array($struct)) {
                foreach ($struct as $key => &$value) {
                    $callback($struct, $key, $value);

                    if (isset($struct[$key]) && is_array($struct[$key])) {
                        $replaceWalker($value, $callback);
                    }
                }
            }
            return $struct;
        };

        $replaced_array = $replaceWalker($struct, $callback);

        if ($this->config["debug"]) {
            $replaced_array["time"] = $this->clockMark();
        }

        return $this->returnTypeConvert($replaced_array);
    }

    /**
     * @param  array|string|\stdClass $struct1
     * @param  array|string|\stdClass $struct2
     * @param  bool                   $slashtoobject
     * @return array|string|\stdClass
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
     * @param array  $assocarray
     * @param array  &$array
     * @param string $currentpath
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
                        if (!empty((array)$assocarray[$key])) {
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
     * @param  array|string|\stdClass $struct1
     * @param  array|string|\stdClass $struct2
     * @param  bool                   $slashtoobject
     * @return array|string|\stdClass
     */
    public function structMerge($struct1, $struct2, $slashtoobject = false)
    {
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
     * @param  array $assocarray
     * @return array
     */
    private function pathSlashToStruct($assocarray)
    {
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
     * @param  array $structpath1_array
     * @param  array $structpath2_array
     * @param  bool  $slashtoobject
     * @return array
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
            foreach ($deltadiff_array as $key => &$value) {
                $value = $this->pathSlashToStruct($value);
            }
        }

        return $deltadiff_array;
    }

    /**
     * @param  array|string|\stdClass $struct
     * @return array|string|\stdClass
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
                return "returntype is not valid!";
                break;
        }
    }

    /**
     * @param  array|string|\stdClass &$struct
     * @param  string                 &$problem
     * @return bool
     */
    private function studyType(&$struct, &$problem)
    {
        if ($this->isJsonString($struct)) {
            $struct = json_decode($struct, true);
            return true;
        } elseif (is_array($struct)) {
            return true;
        } elseif (is_object($struct)) {
            $struct = (array)$struct;
            return true;
        } else {
            $problem = "the parameter is not a valid structure";
            return false;
        }
    }

    /**
     * @param  mixed $string
     * @return bool
     */
    private function isJsonString($string)
    {
        if (!is_string($string)) {
            return false;
        }

        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    private function clockStart()
    {
        $this->time_start = round(microtime(true) * 1000);
    }

    /**
     * @return string
     */
    private function clockMark()
    {
        return round(microtime(true) * 1000) - $this->time_start . " milliseconds";
    }

    private function switchType()
    {
        $aux = $this->config["returntype"];
        $this->config["returntype"] = $this->typetowork;
        $this->typetowork = $aux;
    }
}
