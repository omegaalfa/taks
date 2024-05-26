# taks
Task Manager is a lightweight asynchronous task manager for PHP

# Task - Asynchronous Task Manager

Task is a simple asynchronous task manager written in PHP. It allows you to execute tasks asynchronously and await their results.

## Installation

You can install Task via Composer. Run the following command in your terminal:

```bash
composer require your-vendor/task
```

# Pré-requisitos

PHP 8.1 ou superior

# Exemplos

```php

use OmegaAlfa\Tasks\Task;

// Define an asynchronous task
Task::async(function() use ($file) {
	echo "Leitura 1 \n";
}, 2);

Task::async(function() use ($file) {
	echo "Leitura 2 \n";
}, 1);

Task::async(function() use ($file) {
	Task::sleep(3);
	echo "Leitura 3 \n";
});
Task::async(function() use ($file) {
	Task::sleep(2);
	echo "Leitura 4 \n";
});

Task::run();

```

## Contribuição

Se desejar contribuir com melhorias ou correções, fique à vontade para criar uma pull request ou abrir uma issue no repositório.

## Licença

Este projeto está licenciado sob a Licença MIT.

