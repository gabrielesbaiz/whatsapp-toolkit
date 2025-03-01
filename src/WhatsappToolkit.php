<?php

namespace Gabrielesbaiz\WhatsappToolkit;

class WhatsappToolkit
{
    /**
     * Format message.
     *
     * @param  string|null $message
     * @return string
     */
    public static function formatMessage(?string $message): string
    {
        if (! $message) {
            return '';
        }

        $message = str_replace('&nbsp;', ' ', $message);

        $message = str_replace('</p><p>', '</p> <p>', $message);

        $message = str_replace(['<br>', '<br/>', '<br />'], "\n", $message);

        $message = preg_replace_callback('/<b>\s*(.*?)\s*<\/b>/', function ($matches) {
            return ' *' . trim($matches[1]) . '* ';
        }, $message);

        $message = preg_replace_callback('/<strong>\s*(.*?)\s*<\/strong>/', function ($matches) {
            return ' *' . trim($matches[1]) . '* ';
        }, $message);

        $message = preg_replace_callback('/<i>\s*(.*?)\s*<\/i>/', function ($matches) {
            return ' _' . trim($matches[1]) . '_ ';
        }, $message);

        $message = preg_replace_callback('/<em>\s*(.*?)\s*<\/em>/', function ($matches) {
            return ' _' . trim($matches[1]) . '_ ';
        }, $message);

        $message = preg_replace_callback('/<s>\s*(.*?)\s*<\/s>/', function ($matches) {
            return ' ~' . trim($matches[1]) . '~ ';
        }, $message);

        $message = preg_replace_callback('/<del>\s*(.*?)\s*<\/del>/', function ($matches) {
            return ' ~' . trim($matches[1]) . '~ ';
        }, $message);

        $message = preg_replace_callback('/<pre>\s*(.*?)\s*<\/pre>/s', function ($matches) {
            return '```' . trim($matches[1]) . "```\n\n";
        }, $message);

        $message = preg_replace('/<blockquote>\s*<p>(.*?)<\/p>\s*<\/blockquote>/s', '<blockquote>$1</blockquote>', $message);

        $message = preg_replace_callback('/<blockquote>\s*(.*?)\s*<\/blockquote>/s', function ($matches) {
            $blockquoteText = preg_replace('/\n\s*/', "\n> ", trim($matches[1]));

            return '> ' . $blockquoteText . "\n\n";
        }, $message);

        $message = preg_replace_callback('/<ul>\s*(.*?)\s*<\/ul>/s', function ($matches) {
            return preg_replace_callback('/<li>\s*(.*?)\s*<\/li>/', function ($liMatches) {
                return '- ' . trim($liMatches[1]) . "\n";
            }, $matches[1]) . "\n";
        }, $message);

        $olCount = 1;

        $message = preg_replace_callback('/<ol>\s*(.*?)\s*<\/ol>/s', function ($matches) use (&$olCount) {
            return preg_replace_callback('/<li>\s*(.*?)\s*<\/li>/', function ($liMatches) use (&$olCount) {
                return ($olCount++) . '. ' . trim($liMatches[1]) . "\n";
            }, $matches[1]) . "\n";
        }, $message);

        $message = preg_replace('/\s*<p>\s*(.*?)\s*<\/p>\s*/', "$1\n\n", $message);

        $message = trim($message);

        $message = strip_tags($message);

        return urlencode($message);
    }

    /**
     * Format phone number.
     *
     * @param  string|null $phoneNumber
     * @return string
     */
    public static function formatPhoneNumber(?string $phoneNumber): string
    {
        return urlencode($phoneNumber);
    }

    /**
     * Composer url.
     *
     * @param  string|null $phoneNumber
     * @param  string|null $message
     * @return string
     */
    public static function url(?string $phoneNumber, ?string $message): string
    {
        $phoneNumber = self::formatPhoneNumber($phoneNumber);

        $message = self::formatMessage($message);

        return "https://api.whatsapp.com/send?phone={$phoneNumber}&text={$message}";
    }
}
