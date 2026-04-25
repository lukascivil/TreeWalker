# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```sh
composer install                      # install dev deps (phpunit 5, phpcs)
composer test                         # run phpunit with coverage-text
composer check-format                 # phpcs PSR2 lint over src/
composer format                       # phpcbf PSR2 autofix over src/ and tests/

vendor/bin/phpunit tests/TreeWalkerTest.php          # run a single test file
vendor/bin/phpunit --filter testSimpleStructs        # run a single test method
```

CI (`.github/workflows/php.yml`) runs `composer test` on push and uploads `coverage.xml` to Codecov. PHP >= 5.5 is the supported floor.

## Architecture

Single-class library: `src/TreeWalker.php` (no namespace; PSR-4 autoload maps `""` → `src/`). Tests live in `tests/`; an interactive demo is in `example/index.php`.

The public API operates on three interchangeable structure shapes — JSON string, `\stdClass` object, or associative array — configured per-instance via the constructor's `returntype` option (`"jsonstring" | "object" | "array"`) plus a `debug` flag that appends a `time` key to outputs.

Two pieces of internal machinery are load-bearing and worth understanding before editing any public method:

1. **`studyType(&$struct, &$problem)`** is called at the top of every public method. It normalizes the input in place to an associative array (decoding JSON strings, casting objects), or returns `false` with an error message. Public methods always work in array-space internally.

2. **`returnTypeConvert($struct)`** is the matching exit converter, encoding the result back to whatever `returntype` was configured. New public methods must funnel through this — never return raw arrays.

The diff/merge implementation uses a **path-flattening** strategy rather than recursive structural comparison:

- `structPathArray()` walks a nested structure and produces a flat associative array keyed by slash-delimited paths (e.g. `"cafeina/ss/ff" => 21`).
- `getdiff()` and `structMerge()` both flatten both inputs, then operate on the flat maps (`structPathArrayDiff`, `array_merge`).
- `pathSlashToStruct()` is the inverse — it re-nests slash-keys back into a tree, and is gated by the `$slashtoobject` parameter on `getdiff` / `structMerge`. Note the README examples pass `true` to mean "no slashes / nested output" — the boolean naming is counter-intuitive.
- `pathSlashToStruct()` calls `createDynamicallyObjects()` + `setDynamicallyValue()` internally; those helpers depend on `returntype` being `"array"`, so it temporarily flips the config via `switchType()` and flips it back at the end. Anything that calls these helpers from a non-array return-type context must do the same dance.

`walker()` is the only method that does true recursive in-place mutation (callback receives `&$struct, $key, &$value`); it does not go through the path-flattening pipeline.
