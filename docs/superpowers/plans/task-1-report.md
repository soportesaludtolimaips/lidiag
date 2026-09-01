# Task 1 Report: Backup and update composer.json

## Status: DONE

## What Changed

1. Backed up `composer.json` to `composer.json.bak`
2. Updated `php` requirement: `^8.2` → `^8.3`
3. Updated `laravel/framework`: `^12.0` → `^13.0`
4. Updated `phpunit/phpunit`: `^11.0` → `^12.0`

## Diff

```diff
--- a/composer.json
+++ b/composer.json
@@ -8,7 +8,7 @@
     "license": "MIT",
     "require": {
-        "php": "^8.2",
+        "php": "^8.3",
         "dompdf/dompdf": "^3.1",
         "guzzlehttp/guzzle": "^7.2",
-        "laravel/framework": "^12.0",
+        "laravel/framework": "^13.0",
         "laravel/sanctum": "^4.0",
@@ -29,7 +29,7 @@
         "nunomaduro/collision": "^8.0",
         "phpstan/phpstan": "^2.2",
-        "phpunit/phpunit": "^11.0"
+        "phpunit/phpunit": "^12.0"
     },
```

## Concerns

- **MessageBag patch**: The `extra.patches` section still references `patches/messagebag-arraypush-fix.patch` for `laravel/framework`. This may conflict with Laravel 13 if the issue has been fixed upstream. Will check in Task 3.
- **laravel/ui `^4.0`**: Compatibility with Laravel 13 is uncertain. Will check in Task 3.
- **Not committed yet**: Edits only — commit deferred per task instructions.
