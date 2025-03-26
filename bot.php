<?php
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/language.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

$botToken = $_ENV['TELEGRAM_BOT_TOKEN'];
$telegram = new Api($botToken);


$db = new Database();



// Read incoming webhook JSON payload
$update = json_decode(file_get_contents('php://input'), true);
if (!$update || !isset($update['message'])) {
    http_response_code(200);
    exit;
}

if (isset($update['callback_query'])) {
    // Callback query fields
    $callbackQueryId = $update["callback_query"]["id"] ?? '';
    $callbackData = $update['callback_query']['data'] ?? '';
    $message_id = $update['callback_query']['message']['message_id'] ?? '';
    $chatId = $update['callback_query']['from']['id'] ?? '';
    $chat_type = $update['callback_query']['message']['chat']['type'];
    $callbackFrom = $update['callback_query']['from'] ?? [];
    $username = $callbackFrom['username'] ?? '';
    $firstname = $callbackFrom['first_name'] ?? '';
    $lastname = $callbackFrom['last_name'] ?? '';
    $language_code = $callbackFrom['language_code'] ?? '';

} else {
    // Message fields
    $text = $update['message']['text'] ?? '';
    $chatId = $update['message']['from']['id'] ?? '';
    $chat_type = $update['message']['chat']['type'] ?? '';
    $firstname = $update['message']['from']['first_name'] ?? '';
    $lastname = $update['message']['from']['last_name'] ?? '';
    $username = $update['message']['from']['username'] ?? '';
    $language_code = $update['message']['from']['language_code'] ?? '';
    $message_id = $update['message']['message_id'] ?? '';
    $date = $update['message']['date'] ?? '';

    // Media handling
    $location = $update['message']['location'] ?? null;
    $photo = $update['message']['photo'] ?? [];
    $audio = $update['message']['audio'] ?? [];
    $document = $update['message']['document'] ?? [];
    $video = $update['message']['video'] ?? [];
    $voice = $update['message']['voice'] ?? [];
    $contact = $update['message']['contact'] ?? [];

}
// Language manager instance
$langManager = new LanguageManager($db->getUserLanguage($chatId));


if ($text === "/start") {
    if ($db->userExists($chatId)) {
        $welcomeMessage = $langManager->get('welcome_back', ['username' => $username]);
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $welcomeMessage,
            'parse_mode' => 'MarkdownV2'
        ]);
    } else {
        $db->insertUser($chatId);
        $welcomeMessage = "*Welcome " . escapeMarkdownV2($username) . "*\\ 🎉\n" .
            "Use `/plans` to see VPN packages\\.";
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $welcomeMessage,
            'parse_mode' => 'MarkdownV2'
        ]);
        error_log("New user added: " . $chatId);
    }

}
if ($text === "/language" || $text === "/lang") {
    $keyboard = new Keyboard([
        'inline_keyboard' => [
            [
                ['text' => '🇬🇧 English', 'callback_data' => 'lang_en'],
                ['text' => '🇮🇷 فارسی', 'callback_data' => 'lang_fa']
            ]
        ]
    ]);

    $langManager = new LanguageManager($db->getUserLanguage($chatId));
    $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => $langManager->get('change_language'),
        'reply_markup' => $keyboard
    ]);
}
// Handle language selection callback
if (isset($callbackData) && strpos($callbackData, 'lang_') === 0) {
    $newLang = substr($callbackData, 5);
    $db->setUserLanguage($chatId, $newLang);
    // Send confirmation message in new language
    $langManager = new LanguageManager($newLang);
    $telegram->answerCallbackQuery([
        'callback_query_id' => $callbackQueryId,
        'text' => '✅ Language updated | زبان بروز شد'
    ]);
}
function escapeMarkdownV2($text)
{
    $specialChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
    foreach ($specialChars as $char) {
        $text = str_replace($char, "\\" . $char, $text);
    }
    return $text;
}
?>