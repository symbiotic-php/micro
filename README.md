# Symbiotic Micro (BETA EDITION)

**The package is not recommended to be installed for use, it is for developers of micro applications!**

## Installation

```
composer require symbiotic/micro
```

## Description

The basic core of the framework, the assembly is isolated from the [full version of the framework](https://github.com/symbiotic-php/full/)
to compile applications into a single file. At the moment, the application compiler has not yet been written, but
such an opportunity is planned.

Use the full version: [https://github.com/symbiotic-php/full/](https://github.com/symbiotic-php/full/)!

```
composer require symbiotic/full
```

### Usage

```php
$basePath = dirname(__DIR__);// root folder of the project

include_once $basePath. '/vendor/autoload.php';

$config = include $basePath.'/vendor/symbiotic/micro/src/config.sample.php';

//.. Redefining the configuration array

// Basic construction of the Core container
$core = new \Symbiotic\Core\Core($config);

// Starting request processing
$core->run();

// Then the initialization code and the work of another framework can go on when the symbiosis mode is enabled...
// $laravel->handle();

```


