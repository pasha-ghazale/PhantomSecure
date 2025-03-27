<?php

/**
 * Utility functions for the Telegram bot.
 */
class Utils
{
    /**
     * Escapes special characters in a string for Telegram's MarkdownV2 format.
     *
     * @param string $text The text to escape.
     * @return string The escaped text.
     */
    public static function escapeMarkdownV2(string $text): string
    {
        $specialChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        foreach ($specialChars as $char) {
            $text = str_replace($char, "\\" . $char, $text);
        }
        return $text;
    }
}