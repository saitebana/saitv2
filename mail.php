<?php
require_once __DIR__.'/includes/config.php';

header('Content-Type: application/json');

// Функция для отправки JSON ответа
function jsonResponse(bool $success, string $message = '', array $data = [], int $code = 200): void {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Проверка метода
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Метод не разрешен', [], 405);
    }

    // Получение данных
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(false, 'Неверный JSON: '.json_last_error_msg(), [], 400);
    }

    // Валидация
    $required = ['name', 'phone', 'event'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        jsonResponse(false, 'Заполните поля: '.implode(', ', $missing), [], 422);
    }

    // Обработка данных
    $cleanData = [
        'name' => htmlspecialchars(trim($data['name'])),
        'phone' => preg_replace('/[^0-9+]/', '', $data['phone']),
        'event' => htmlspecialchars(trim($data['event'])),
        'date' => $data['date'] ?? null,
        'guests' => $data['guests'] ?? null,
        'message' => $data['message'] ?? null
    ];

    // Формирование сообщения
    $message = "Новая заявка:\nИмя: {$cleanData['name']}\nТелефон: {$cleanData['phone']}\nМероприятие: {$cleanData['event']}";
    if ($cleanData['date']) $message .= "\nДата: {$cleanData['date']}";
    if ($cleanData['guests']) $message .= "\nГостей: {$cleanData['guests']}";
    if ($cleanData['message']) $message .= "\nСообщение: {$cleanData['message']}";

    // Отправка в Telegram
    $telegramUrl = "https://api.telegram.org/bot".TELEGRAM_TOKEN."/sendMessage";
    $telegramData = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $telegramUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $telegramData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5
    ]);
    
    $telegramResponse = curl_exec($ch);
    curl_close($ch);

    // Отправка email
    $headers = [
        'From: '.MAIL_FROM,
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP/'.phpversion()
    ];
    
    $mailSent = mail(
        ADMIN_EMAIL,
        'Новая заявка: '.$cleanData['event'],
        $message,
        implode("\r\n", $headers)
    );

    // Ответ
    jsonResponse(true, 'Заявка принята', [
        'telegram' => $telegramResponse !== false,
        'email' => $mailSent
    ]);

} catch (Throwable $e) {
    error_log('Mail error: '.$e->getMessage());
    jsonResponse(false, 'Ошибка сервера', [], 500);
}