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

// Get webhook info
$response = $telegram->getWebhookInfo();
var_dump($response);

$db = new Database();



// Read incoming webhook JSON payload
$update = json_decode(file_get_contents('php://input'), true);
error_log("Incoming update: " . json_encode($update));
if (!$update || !isset($update['message'])) {
    http_response_code(200);
    exit;
}

if (isset($update['callback_query'])) {
    error_log("Received callback_query: " . json_encode($update['callback_query']));
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

    if (strpos($callbackData, 'lang_') === 0) {
        $newLang = substr($callbackData, 5);
        try {
            // Update the user's language in the database
            error_log("Updating language for user $chatId to $newLang");
            $db->setUserLanguage($chatId, $newLang);
            error_log("Language updated in DB for user $chatId");

            // Create a new language manager instance
            error_log("Creating LanguageManager for language $newLang");
            $langManager = new LanguageManager($newLang);
            error_log("LanguageManager created for language $newLang");

            // Answer the callback query
            $telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '✅ Language updated | زبان بروز شد'
            ]);

            // Edit the original message
            $changeLanguageText = $langManager->get('change_language');
            error_log("Change language text: $changeLanguageText");
            $telegram->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $message_id,
                'text' => escapeMarkdownV2($changeLanguageText),
                'parse_mode' => 'MarkdownV2',
                'reply_markup' => new Keyboard([
                    'inline_keyboard' => [
                        [
                            ['text' => '🇬🇧 English', 'callback_data' => 'lang_en'],
                            ['text' => '🇮🇷 فارسی', 'callback_data' => 'lang_fa']
                        ]
                    ]
                ])
            ]);

            // Send a confirmation message
            $languageUpdatedText = $langManager->get('language_updated');
            error_log("Language updated text: $languageUpdatedText");
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => escapeMarkdownV2($languageUpdatedText),
                'parse_mode' => 'MarkdownV2'
            ]);
        } catch (Exception $e) {
            error_log("Error in callback query handling: " . $e->getMessage());
        }
    }
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

    $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => $langManager->get('change_language'),
        'reply_markup' => $keyboard
    ]);
}
if (isset($callbackData) && strpos($callbackData, 'lang_') === 0) {
    $newLang = substr($callbackData, 5);
    $db->setUserLanguage($chatId, $newLang);

    // Create new language manager instance with updated language
    $langManager = new LanguageManager($newLang);

    // Answer the callback query
    $telegram->answerCallbackQuery([
        'callback_query_id' => $callbackQueryId,
        'text' => '✅ Language updated | زبان بروز شد'
    ]);

    // Update the original message with new language
    $telegram->editMessageText([
        'chat_id' => $chatId,
        'message_id' => $message_id,
        'text' => $langManager->get('change_language'),
        'reply_markup' => new Keyboard([
            'inline_keyboard' => [
                [
                    ['text' => '🇬🇧 English', 'callback_data' => 'lang_en'],
                    ['text' => '🇮🇷 فارسی', 'callback_data' => 'lang_fa']
                ]
            ]
        ])
    ]);

    // Send confirmation message in new language
    $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => $langManager->get('language_updated'),
        'parse_mode' => 'MarkdownV2'
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