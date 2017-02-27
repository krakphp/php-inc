# Php Inc

Php inc is a utility for generating a single include file of all the files that won't be auto loaded by a Psr compliant autoloader.

Any files that hold functions or constants that don't belong in classes will need to be included manually, but managing all of the `require_once` declarations can be quite a pain.

## Installation

Install via composer at `krak/php-inc`

## Usage

### CLI

The main interface is the `bin/php-inc` command which allows you to run generate a php source file with all of your project's require's.

```
./bin/php-inc {path-to-source} > src/inc.php
```

You may want to make sure your `inc.php` file (or whatever you decide to name it) is ignored by version control, and then generate the file when you build your project.

### Integrating in your own Console Application

If you are using Symfony or Laravel console application, you can just add the command to your own application with `Krak\PhpInc\Command\GenerateCommand`.
