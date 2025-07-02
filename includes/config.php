<?php
declare(strict_types=1);

// Загрузка окружения
require_once __DIR__.'/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Базовые настройки
define('BEGET_SECURITY', true);
define('ENVIRONMENT', $_ENV['APP_ENV'] ?? 'production');
define('BASE_URL', 'https://prazdniksytkami.ru');
define('SITE_NAME', 'Праздник сУтками! - Организация детских праздников!');

// Настройки БД
define('DB_HOST', $_ENV['DB_HOST']);
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);

// Почтовые настройки
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL']);
define('MAIL_FROM', $_ENV['MAIL_FROM']);
define('MAIL_PASS', $_ENV['MAIL_PASS']);

// Telegram
define('TELEGRAM_TOKEN', $_ENV['TELEGRAM_TOKEN']);
define('TELEGRAM_CHAT_ID', $_ENV['TELEGRAM_CHAT_ID']);

// Пути
define('LOGS_DIR', __DIR__.'/logs');
define('TEMPLATES_DIR', __DIR__.'/templates');

// Создание директорий
if (!file_exists(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0755, true);
    file_put_contents(LOGS_DIR.'/.htaccess', 'Deny from all');
}

// Настройки ошибок
ini_set('log_errors', '1');
ini_set('error_log', LOGS_DIR.'/php_errors.log');

if (ENVIRONMENT === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}