<?php

declare(strict_types=1);

/**
 * Measures the formatter against the version 1 implementation.
 *
 * Version 1 is reproduced here verbatim rather than described, so the numbers
 * come from running both rather than from reasoning about them.
 *
 * Usage: composer bench
 */

require __DIR__.'/../vendor/autoload.php';

use Gabrielesbaiz\WhatsappToolkit\Formatters\HtmlFormatter;
use Gabrielesbaiz\WhatsappToolkit\Support\Memo;

final class LegacyFormatter
{
    public static function formatMessage(?string $message): string
    {
        if (! $message) {
            return '';
        }

        $message = str_replace('&nbsp;', ' ', $message);
        $message = str_replace('</p><p>', '</p> <p>', $message);
        $message = str_replace(['<br>', '<br/>', '<br />'], "\n", $message);

        foreach ([['b', '*'], ['strong', '*'], ['i', '_'], ['em', '_'], ['s', '~'], ['del', '~']] as [$tag, $marker]) {
            $message = preg_replace_callback("/<{$tag}>\s*(.*?)\s*<\/{$tag}>/", static fn (array $m): string => ' '.$marker.trim($m[1]).$marker.' ', (string) $message);
        }

        $message = preg_replace_callback('/<pre>\s*(.*?)\s*<\/pre>/s', static fn (array $m): string => '```'.trim($m[1])."```\n\n", (string) $message);
        $message = preg_replace('/<blockquote>\s*<p>(.*?)<\/p>\s*<\/blockquote>/s', '<blockquote>$1</blockquote>', (string) $message);
        $message = preg_replace_callback('/<blockquote>\s*(.*?)\s*<\/blockquote>/s', static function (array $m): string {
            return '> '.preg_replace('/\n\s*/', "\n> ", trim($m[1]))."\n\n";
        }, (string) $message);

        $message = preg_replace_callback('/<ul>\s*(.*?)\s*<\/ul>/s', static function (array $m): string {
            return preg_replace_callback('/<li>\s*(.*?)\s*<\/li>/', static fn (array $li): string => '- '.trim($li[1])."\n", $m[1])."\n";
        }, (string) $message);

        $olCount = 1;

        $message = preg_replace_callback('/<ol>\s*(.*?)\s*<\/ol>/s', static function (array $m) use (&$olCount): string {
            return preg_replace_callback('/<li>\s*(.*?)\s*<\/li>/', static function (array $li) use (&$olCount): string {
                return ($olCount++).'. '.trim($li[1])."\n";
            }, $m[1])."\n";
        }, (string) $message);

        $message = preg_replace('/\s*<p>\s*(.*?)\s*<\/p>\s*/', "$1\n\n", (string) $message);

        return urlencode(strip_tags(trim((string) $message)));
    }
}

$cases = [
    'plain text' => 'Buongiorno Mario, la richiamo nel pomeriggio.',
    'small html' => '<p>Buongiorno <b>Mario</b>,</p><p>il preventivo <i>Q-7</i> è pronto.</p>',
    'editor output' => str_repeat(
        '<p>Buongiorno <strong>Mario</strong>, il preventivo &egrave; pronto.</p>'
        .'<ul><li>Polizza RCA</li><li>Assistenza</li></ul>'
        .'<blockquote><p>Valido 30 giorni</p></blockquote>'
        .'<p>Saluti, <a href="https://novias.it">Novias</a></p>',
        8,
    ),
];

$iterations = 2000;

printf("%-16s %12s %12s %10s\n", 'case', 'v1 (ms)', 'v2 (ms)', 'speedup');
printf("%s\n", str_repeat('-', 54));

foreach ($cases as $label => $body) {
    // Memoization is disabled here: repeating one body 2000 times would
    // otherwise measure the memo rather than the conversion.
    $formatter = new HtmlFormatter(new Memo(128, false));

    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        LegacyFormatter::formatMessage($body);
    }
    $legacy = (hrtime(true) - $start) / 1e6;

    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $formatter->format($body);
    }
    $current = (hrtime(true) - $start) / 1e6;

    printf("%-16s %12.2f %12.2f %9.1fx\n", $label, $legacy, $current, $legacy / max($current, 0.0001));
}

// And the case the memo exists for: one body, many rows.
$formatter = new HtmlFormatter(new Memo);
$body = $cases['editor output'];

$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $formatter->format($body);
}
printf("\n%-16s %12s %12.2f\n", 'memoized', '-', (hrtime(true) - $start) / 1e6);
