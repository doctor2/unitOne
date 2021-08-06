
## Запуск приложения

php index.php

## Пример

```
$juniReverseTemplating = new JuniReverseTemplating();

$juniReverseTemplating->parseVariables('Hello, my name is {{name}}.', 'Hello, my name is Juni.');
```

## Тесты

./vendor/bin/phpunit

## Статический анализ

php vendor/bin/psalm

php vendor/bin/phan
