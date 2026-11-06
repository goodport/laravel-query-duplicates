# AGENTS.md

## О пакете

`goodport/laravel-query-duplicates` — инструмент разработки и тестирования для Laravel: находит дублирующиеся SQL-запросы и N+1 внутри HTTP-запросов. Устанавливается в `require-dev` и не предназначен для production-мониторинга.

## Стек

- PHP 8.3+
- Laravel 12
- `psr/log` ^3.0
- Тесты: Orchestra Testbench, PHPUnit 11
- Форматирование: Laravel Pint

## Структура

- `src/QueryDuplicatesServiceProvider.php` — регистрация listener и middleware.
- `src/Http/TrackDuplicateQueries.php` — жизненный цикл сбора на HTTP-запрос.
- `src/Listener/RecordQuery.php` — подписка на `QueryExecuted`.
- `src/Aggregator/DuplicateQueryAggregator.php` — группировка запросов и правила отчёта.
- `src/Aggregator/QueryGroup.php` — данные группы, типы `duplicate` и `n+1`.
- `src/Config/DuplicateQuerySettings.php` — разбор и валидация конфига.
- `src/Logging/` — запись отчёта и выбор канала.
- `src/Support/` — исключения SQL и определение N+1.
- `config/query-duplicates.php` — публикуемый конфиг.
- `tests/` — Unit, Feature, Integration и Support-хелперы.

## Ключевое поведение

- Отслеживаются только SQL внутри HTTP-запроса; console, queue, scheduler и Octane вне scope.
- N+1 имеет приоритет над duplicate.
- Порог срабатывания — `threshold`.
- В отчёт не попадает query string.
- Память ограничена числом уникальных SQL и лимитом наборов bindings.

## Команды

- `composer install`
- `composer test` или `vendor/bin/phpunit`
- `vendor/bin/pint --dirty`

## Тесты

- Используют Orchestra Testbench, фейковое соединение и файловые log-каналы.
- Реальные БД и сеть не нужны.
- Запускай только связанные с изменением тесты.

## Правила

- Пакет публичный: только обезличенные примеры, без упоминаний наших проектов и данных. Подробности — в `project.md` (`.kilo/rules/` или `.gigacode/rules/`).
- Не добавляй зависимости от моделей, контроллеров, роутов и БД конкретного приложения.
- Меняй публичный API и конфиг обратносовместимо.
- Этот файл описывает структуру пакета; обновляй его при изменении архитектуры.
