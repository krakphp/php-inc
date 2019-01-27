# Php Inc

![PHP Requirements](https://img.shields.io/badge/php-%5E7.1-8892BF.svg)

Php inc is a composer plugin for automatically including certain files into composer's `autoload` and `autoload-dev` `files` config. Given a set of file matchers, on the the `dump-autoload` event, php-inc will automatically include any matched files into the dumped autoloaded files.

This ameliorates the issues that come about when you want to include certain files that contain functions or maybe multiple classes but don't want to constantly update the composer autoload files configuration which can get hard to deal with when you start including more files.

## Installation

Install via composer at `krak/php-inc`

## Usage

With the default configuration, simply name any file in your `src` or `tests` directory starting with a lower case letter with a `.php` extension will automatically be included in the dumped autoload files when composer's `dump-autoload` event is triggered. This happens on install, update, or dump-autoload commands.

The composer plugin is automatically loaded after it is installed, so in most scenarios, you shouldn't have to do anything for the files to be automatically included. However, if you add files and want to include them right away during development, you can run `composer dump-autoload` to make sure they are included.

## Configuration

Here's an example of the default configuration that is applied:

```json
{
  "extra": {
    "php-inc": {
      "src-path": "src",
      "test-path": "tests",
      "matches": {
        "type": "and",
        "matches": [
          {"type":  "ext", "exts":  ["php"]},
          {"type":  "lowerCase"},
          {"type":  "excludePath", "path":  "@.*/(Resources|Tests)/.*@"}
        ]
      },
      "matches-dev-src": {
        "type": "and",
        "matches": [
          {"type":  "ext", "exts":  ["php"]},
          {"type":  "lowerCase"},
          {"type":  "includePath", "path":  "@.*/Tests/.*@"},
          {"type":  "excludePath", "path":  "@.*/Tests/.*/Fixtures/.*@"}
        ]
      },
      "matches-dev-test": {
        "type": "and",
        "matches": [
          {"type":  "ext", "exts":  ["php"]},
          {"type":  "lowerCase"},
          {"type":  "excludePath", "path":  "@.*/Fixtures/.*@"}
        ]
      }
    }
  }
}
```

Let's go through and explain what each part means and refers to.

### src-path

`src-path` will determine the path to your source code where any autoload files will be searched in.

If you are working with the standard Laravel file structure, you'll want to change the src-path to `app` instead of `src`.

### test-path

`test-path` will determine the path to your test code where the autoload-dev files will be searched.

### matches

`matches` can be any hierarchy of configured matches to determine how you want the src folder to be searched for files to be included in `autoload.files`. The default configuration ensures that all files that start with a lower case file name, have a `php` extension, and are not inside of a Resources or Tests directory will be included in the `autoload.files` composer configuration.

### matches-dev-src

`matches-dev-src` can be any hierarchy of configured matches to determine how you want the src folder to be searched for files to be included in `autoload-dev.files`. The default configuration ensures that all files that start with a lower case file name, have a `php` extension, are inside of a `Tests` directory, and *not* apart of a `Fixtures` directory will be included in the `autoload-dev.files` composer configuration.

### matches-dev-test

`matches-dev-test` can be any hierarchy of configured matches to determine how you want the test folder to be searched for files to be included in `autoload-dev.files`. The default configuration ensures that all files that start with a lower case file name, have a `php` extension, and are *not* apart of a `Fixtures` directory will be included in the `autoload-dev.files` composer configuration.

## Debugging

If you are ever curious what files are being included, you can simply run `composer dump-autoload -v` and view the php-inc output to see which files are being merged with which composer files definition.

## Managing Dependencies

With extended use, you may come into a situation where one file included needs to be loaded before another. If this comes up, the best solution I've found for now is to prefix those files with an `_` and just create a new file named inc.php which loads them in the correct order.

For example:

```
src/a.php
src/b.php
```

`a.php` depends on `b.php` loading first. To enforce loading order, we'd make the following change:

```
src/_a.php
src/_b.php
src/inc.php
```

Where `inc.php` is as follows:

```php
<?php

require_once __DIR__ . '/_b.php';
require_once __DIR__ . '/_a.php';
```

When you run `composer dump-autoload`, only `inc.php` will be included and will make sure to include those files correctly.

## Why is this useful?

https://nikic.github.io/2012/08/10/Are-PHP-developers-functophobic.html

Until php includes a spec for function autoloading, creating and using standard functions within a modern psr-4 codebase is cumbersome, especially compared to the simplicity of using autoloaded classes. Most devs will give up on using functions and just create abstract classes with static functions to circumvent the autoloading constraints instead of manually registering individual files in the composer autoload sections.

This is useful for more than just functions however. There are plenty of cases where one file with multiple definitions would make sense to keep together instead of splitting into several files which clutter the filesystem.

This plugin is an attempt to help php devs who use composer to have the ability to

## Drawbacks

The main drawbacks to automatically including the php files in the composer autoload files section is that those files will be included anytime the composer autoloader is loaded. In larger projects, this can be a concern if you are loading files that are only needed during certain, less frequent paths of execution.

Opcache does mitigate this problem tremendously, but it is something to consider when you start sprinkling files to be included throughout your codebase.
