# Symbiotic Micro (BETA EDITION)

**Пакет не рекомендуется ставить для использования, он для разработчиков микро приложений!**

## Установка

```
composer require symbiotic/micro
```

## Описание

Базовое ядро фреймворка, данная сборка выделена из [основной версии фреймворка](https://github.com/symbiotic-php/full/)
для компиляции ядра и приложений в один файл. На данный момент еще не написан компилятор приложений, но планируется
такая возможность.

Используйте полную версию: [https://github.com/symbiotic-php/full/](https://github.com/symbiotic-php/full/)!

```
composer require symbiotic/full
```

## Начало работы

```php

$basePath = dirname(__DIR__);// корневая папка проекта

include_once $basePath. '/vendor/autoload.php';

$config = include $basePath.'/vendor/symbiotic/full/src/config.sample.php';

//.. Ваши настройки конфига

// Базовая постройка контейнера
$core = new \Symbiotic\Core\Core($config);
    
// Запуск 
$core->run();
// Дальше может идти код инициализации и отработки другого фреймворка...

```

