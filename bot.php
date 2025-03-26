<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Access variables
$botToken = $_ENV['TELEGRAM_BOT_TOKEN'];
$webhookUrl = $_ENV['WEBHOOK_URL'];
$dbHost = $_ENV['DB_HOST'];
$dbUser = $_ENV['DB_USER'];
$dbPass = $_ENV['DB_PASS'];

use Telegram\Bot\Api;

$telegram = new Api($botToken);

$updates = $telegram->getUpdates();
foreach ($updates as $update) {
    $chatId = $update['message']['chat']['id'];
    $text = strtolower($update['message']['text']);

    if ($text === '/start') {
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Welcome! Use /plans to see VPN packages."
        ]);
    }
}

?>