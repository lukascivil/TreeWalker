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

    private $typeToWork = "array";
    private $timeStart = 0;
    private $timeEnd = 0;

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
     * @param  array                  $keypathArray
     * @return \stdClass|string|array
     */
    public function getDynamicallyValue($struct, $keypathArray)
    {
        if (!$this->studyType($struct, $problem)) {
            return $problem;
        }

        $getDynamically = function ($structAssocarray, $keypathArray) use (&$getDynamically) {
            if (empty($keypathArray)) {
                return $structAssocarray;
            }

            $key = array_shift($keypathArray);

            if (is_array($structAssocarray)) {
                if (array_key_exists($key, $structAssocarray)) {
                    return $getDynamically($structAssocarray[$key], $keypathArray);
                } else {
                    return '{"error": "Error, some key does not exist!"}';
                }
            }
        };

        $value = $getDynamically($struct, $keypathArray);

        return $this->returnTypeConvert($value);
    }

    /**
     * @param  \stdClass|string|array $struct
     * @param  array                  $keypathArray
     * @param  mixed                  $value
     * @return \stdClass|string|array
     */
    public function setDynamicallyValue($struct, $keypathArray, $value = "")
    {
        if (!$this->studyType($struct, $problem)) {
            return $problem;
        }

        $setDynamically = function (&$structAssocarray, $keypathArray, $value) use (&$setDynamically) {
            if (count($keypathArray) === 1) {
                $structAssocarray[$keypathArray[0]] = $value;
                return;
            }

            $key = array_shift($keypathArray);

            if (is_array($structAssocarray)) {
                if (array_key_exists($key, $structAssocarray)) {
                    return $setDynamically($structAssocarray[$key], $keypathArray, $value);
                } else {
                    return '{"error": "Error, some key does not exist!"}';
                }
            }
        };

        $setDynamically($struct, $keypathArray, $value);

        return $this->returnTypeConvert($struct);
    }

    /**
     * @param  array|string|\stdClass $struct
     * @param  array                  $keypathArray
     * @return array|string|\stdClass
     */
    public function createDynamicallyObjects($struct, $keypathArray)
    {
        if (!$this->studyType($struct, $problem)) {
            return $problem;
        }

        $pathString = "";

        for ($i = 0; $i < count($keypathArray); $i++) {
            $key = $keypathArray[$i];
            $pathString .= $key . "/";
        }

        $this->accessDynamically($pathString, $struct);
        return $this->returnTypeConvert($struct);
    }

    /**
     * @param string $pathString
     * @param array  $array
     */
    private function accessDynamically($pathString, &$array)
    {
        $keys = explode('/', substr_replace($pathString, "", -1));
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

        $replacedArray = $replaceWalker($struct, $callback);

        if ($this->config["debug"]) {
            $replacedArray["time"] = $this->clockMark();
        }

        return $this->returnTypeConvert($replacedArray);
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

        $structPath1Array = array();
        $structPath2Array = array();

        $this->structPathArray($struct1, $structPath1Array, "");
        $this->structPathArray($struct2, $structPath2Array, "");
        $deltaDiffArray = $this->structPathArrayDiff($structPath1Array, $structPath2Array, $slashtoobject);

        if ($this->config["debug"]) {
            $deltaDiffArray["time"] = $this->clockMark();
        }

        return $this->returnTypeConvert($deltaDiffArray);
    }

    /**
     * @param array  $assocArray
     * @param array  &$array
     * @param string $currentPath
     */
    private function structPathArray($assocArray, &$array, $currentPath)
    {
        if (is_array($assocArray)) {
            foreach ($assocArray as $key => $value) {
                if (array_key_exists($key, $assocArray)) {
                    $path = $currentPath !== '' ? $currentPath . "/" . $key : sprintf($key);

                    if (gettype($assocArray[$key]) == "array" && !empty($assocArray[$key])) {
                        $this->structPathArray($assocArray[$key], $array, $path);
                    } elseif (gettype($assocArray[$key]) == "object") {
                        if (!empty((array)$assocArray[$key])) {
                            $this->structPathArray((array)$assocArray[$key], $array, $path);
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

        $structPath1Array = array();
        $structPath2Array = array();

        $this->structPathArray($struct1, $structPath1Array, "");
        $this->structPathArray($struct2, $structPath2Array, "");
        $mergedArray = array_merge($structPath2Array, $structPath1Array);

        if ($this->config["debug"]) {
            $mergedArray["time"] = $this->clockMark();
        }

        if ($slashtoobject) {
            $mergedArray = $this->pathSlashToStruct($mergedArray);
        }

        return $this->returnTypeConvert($mergedArray);
    }

    /**
     * @param  array $assocArray
     * @return array
     */
    private function pathSlashToStruct($assocArray)
    {
        $newAssocArray = [];

        $this->switchType();

        if (is_array($assocArray)) {
            foreach ($assocArray as $key => $value) {
                if (strpos($key, '/') !== false) {
                    $aux = explode("/", $key);
                    $newKey = $aux[0];
                    array_shift($aux);

                    if (isset($newAssocArray[$newKey])) {
                        $newAssocArray[$newKey] = $this->createDynamicallyObjects($newAssocArray[$newKey], $aux);
                        $newAssocArray[$newKey] = $this->setDynamicallyValue($newAssocArray[$newKey], $aux, $value);
                    } else {
                        $newAssocArray[$newKey] = $this->createDynamicallyObjects(array(), $aux);
                        $newAssocArray[$newKey] = $this->setDynamicallyValue($newAssocArray[$newKey], $aux, $value);
                    }
                } else {
                    $newAssocArray[$key] = $value;
                }
            }
        }

        $this->switchType();

        return $newAssocArray;
    }

    /**
     * @param  array $structPath1Array
     * @param  array $structPath2Array
     * @param  bool  $slashtoobject
     * @return array
     */
    private function structPathArrayDiff($structPath1Array, $structPath2Array, $slashtoobject)
    {
        $deltaDiffArray = array(
            "new"     => array(),
            "removed" => array(),
            "edited"  => array()
        );

        foreach ($structPath1Array as $key1 => $value1) {
            if (array_key_exists($key1, $structPath2Array)) {
                if ($value1 !== $structPath2Array[$key1]) {
                    $edited = array(
                        "oldvalue" => $structPath2Array[$key1],
                        "newvalue" => $value1
                    );
                    $deltaDiffArray["edited"][$key1] = $edited;
                }
            } else {
                $deltaDiffArray["new"][$key1] = $value1;
            }
        }

        $removido = array_diff_key($structPath2Array, $structPath1Array);

        if (!empty($removido)) {
            foreach ($removido as $key => $value) {
                $deltaDiffArray["removed"][$key] = $value;
            }
        }

        if ($slashtoobject) {
            foreach ($deltaDiffArray as $key => &$value) {
                $value = $this->pathSlashToStruct($value);
            }
        }

        return $deltaDiffArray;
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
        $this->timeStart = round(microtime(true) * 1000);
    }

    /**
     * @return string
     */
    private function clockMark()
    {
        return round(microtime(true) * 1000) - $this->timeStart . " milliseconds";
    }

    private function switchType()
    {
        $aux = $this->config["returntype"];
        $this->config["returntype"] = $this->typeToWork;
        $this->typeToWork = $aux;
    }
}
