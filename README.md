# TreeWalker

A simple and lightweight PHP library for manipulating nested structures — arrays, objects and JSON strings interchangeably.

[![Build Status](https://github.com/lukascivil/TreeWalker/workflows/PHP%20Composer/badge.svg)]()
[![Total Downloads](https://poser.pugx.org/lukascivil/treewalker/downloads)](https://packagist.org/packages/lukascivil/treewalker)
[![codecov](https://codecov.io/gh/lukascivil/TreeWalker/branch/master/graph/badge.svg)](https://codecov.io/gh/lukascivil/TreeWalker)
[![License](https://poser.pugx.org/lukascivil/treewalker/license.svg)](https://packagist.org/packages/lukascivil/treewalker)

## Methods

| Method | Description |
|--------|-------------|
| `getdiff()` | Returns the difference between two structures |
| `walker()` | Walks recursively through a structure, allowing edits and deletions |
| `structMerge()` | Merges two structures (first argument takes precedence) |
| `createDynamicallyObjects()` | Creates nested keys dynamically |
| `getDynamicallyValue()` | Reads a value by dynamic key path |
| `setDynamicallyValue()` | Sets a value by dynamic key path |

### [Live example](http://treewalker.lukascivil.com.br/)

All methods accept and return any of the three supported structure types:

```
"jsonstring" | "object" | "array"
```

## Requirements

- PHP >= 8.1

## Installation

```bash
composer require lukascivil/treewalker
```

## Usage

### Initialization

```php
<?php

$treeWalker = new TreeWalker([
    "debug"      => false,    // true = append execution time to output
    "returntype" => "array"   // "jsonstring" | "object" | "array"
]);
```

---

### getdiff()

Returns the difference between two structures, split into `new`, `removed` and `edited` keys.

```php
<?php

$struct1 = ["casa" => 1, "b" => "5", "cafeina" => ["ss" => "ddd"], "oi" => 5];
$struct2 = ["casa" => 2, "cafeina" => ["ss" => "dddd"], "oi2" => 5];

$treeWalker->getdiff($struct1, $struct2, false); // false = flat slash-delimited keys
```

Output:

```json
{
    "new": {
        "b": "5",
        "oi": 5
    },
    "removed": {
        "oi2": 5
    },
    "edited": {
        "casa": {
            "oldvalue": 2,
            "newvalue": 1
        },
        "cafeina/ss": {
            "oldvalue": "dddd",
            "newvalue": "ddd"
        }
    }
}
```

Pass `true` as the third argument to get nested output instead of slash-delimited keys:

```php
<?php

$treeWalker->getdiff($struct1, $struct2, true); // true = nested output
```

---

### walker()

Walks recursively through the structure. The callback receives the parent array, the current key and the current value by reference, allowing in-place modification or deletion.

```php
<?php

$struct = ["casa" => 2, "cafeina" => ["ss" => ["ff" => 21, "ff1" => 22]], "oi2" => 5];

$treeWalker->walker($struct, function (&$struct, $key, &$value) {
    if ($key === "ff") {
        unset($struct[$key]);      // delete node
    }

    if ($key === "ff1") {
        $value = ["son" => "tiago"]; // replace value
    }
});
```

Output:

```json
{"casa": 2, "cafeina": {"ss": {"ff1": {"son": "tiago"}}}, "oi2": 5}
```

---

### structMerge()

Merges two structures. Values from the first argument take precedence over the second.

```php
<?php

$struct1 = ["casa" => 1, "b" => "5", "cafeina" => ["ss1" => "1", "ss2" => "2"], "oi" => 5];
$struct2 = ["casa" => 2, "cafeina" => ["ss" => ["ff" => 21, "ff1" => 22]], "oi2" => 5, "ss" => "dddddf"];

$treeWalker->structMerge($struct1, $struct2, true); // true = nested output
```

Output:

```json
{"casa": 1, "b": "5", "cafeina": {"ss1": "1", "ss2": "2", "ss": {"ff": 21, "ff1": 22}}, "oi": 5, "oi2": 5, "ss": "dddddf"}
```

---

### createDynamicallyObjects()

Creates nested empty objects from a dynamic array of keys.

```php
<?php

$struct = ["casa" => 1, "b" => "5", "cafeina" => ["ss" => "ddd"], "oi" => 5];

$treeWalker->createDynamicallyObjects($struct, [1, 2, 5, 9, 10, 11]);
```

Output:

```json
{
    "casa": 1,
    "b": "5",
    "cafeina": {"ss": "ddd"},
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

---

### getDynamicallyValue()

Reads a value from a structure using a dynamic key path.

```php
<?php

$struct = ["casa" => 2, "cafeina" => ["ss" => ["ff" => 21, "ff1" => 22]], "oi2" => 5];

// Static access
$struct["cafeina"]["ss"];

// Dynamic access
$treeWalker->getDynamicallyValue($struct, ["cafeina", "ss"]);
```

Output:

```json
{"ff": 21, "ff1": 22}
```

---

### setDynamicallyValue()

Sets a value in a structure using a dynamic key path.

```php
<?php

$struct = ["casa" => 2, "cafeina" => ["ss" => ["ff" => 21, "ff1" => 22]], "oi2" => 5];

// Static access
$struct["cafeina"]["ss"] = "newvalue";

// Dynamic access
$treeWalker->setDynamicallyValue($struct, ["cafeina", "ss"], "newvalue");
```

Output:

```json
{"casa": 2, "cafeina": {"ss": "newvalue"}, "oi2": 5}
```

---

## Development

```bash
composer install       # install dev dependencies
composer test          # run tests with coverage
composer check-format  # PSR-2 lint
composer format        # PSR-2 autofix
```

## Additional context

If you need the JavaScript equivalent for client-side structure comparison, see [JsonDifference](https://github.com/lukascivil/jsondiffer).

## License

MIT © [Lucas Cordeiro da Silva](https://github.com/lukascivil)
