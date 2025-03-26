<?php
class LanguageManager
{
    private $messages;
    private $defaultLang = 'en';

    public function __construct($lang = 'en')
    {
        $langFile = __DIR__ . "/../languages/{$lang}.php";
        $this->messages = file_exists($langFile)
            ? require $langFile
            : require __DIR__ . "/../languages/{$this->defaultLang}.php";
    }

    public function get($key, $params = [])
    {
        $message = $this->messages[$key] ?? $key;
        foreach ($params as $param => $value) {
            $message = str_replace("{{$param}}", $value, $message);
        }
        return $message;
    }
}