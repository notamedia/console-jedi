# Консольные команды

Console Jedi из коробки содержит ряд команд, упрощающих администрирование и поддержку сайта на «Битриксе». Узнать 
полный список вы можете вызвав консольное приложение:

```bash
./vendor/bin/jedi
```

Подробную информацию о команде можно запросить через опцию `--help`, например:
 
```bash
./vendor/bin/jedi cache:clear --help
```

## Создание команды

Написание собственных команд отличается от 
[написания команд для Symfony Console](http://symfony.com/doc/current/components/console/introduction.html) только 
классом-родителем:

* `\Notamedia\ConsoleJedi\Application\Command\Command` — команда может работать при отсутствии ядра «Битрикса»,
* `\Notamedia\ConsoleJedi\Application\Command\BitrixCommand` — для работы команды обязательно должно быть 
инициализировано ядро «Битрикса».

Во имя модульности проекта, свои консольные команды нужно размещать в модулях «Битрикса». Для этого в файле 
`vendor.module/cli.php` должны быть описаны консольные команды модуля:

```php
<?php

return [
    'commands' => [
         new \Vendor\Module\Command\FirstCommand()
    ]
];
```

Во время запуска Console Jedi автоматически загрузит команды всех установленных модулей. Кроме того, вы можете 
зарегистрировать дополнительные команды через настройки в `.jedi.php` (файл располагается в корне проекта):

```php
<?php

return [ 
    'commands' => [ 
        new FirstCommand()
    ] 
];
```

## Полезные материалы

* [Документация по Symfony Console](http://symfony.com/doc/current/components/console/introduction.html).