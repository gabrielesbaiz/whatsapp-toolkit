<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Formatters;

use Gabrielesbaiz\WhatsappToolkit\Contracts\MessageFormatter;
use Gabrielesbaiz\WhatsappToolkit\Support\Memo;

/**
 * HTML, as a rich-text editor produces it, converted to WhatsApp markup.
 *
 * This is the hot path of the package, and it is shaped by two observations.
 * The first is that most bodies contain no markup at all, so the common case
 * should not pay for the uncommon one — hence the early return before any
 * pattern is touched. The second is that the conversion itself was previously
 * fifteen sequential passes, each allocating a fresh closure; the inline rules
 * are now one pattern map, compiled once and applied in a single call.
 */
final class HtmlFormatter implements MessageFormatter
{
    /**
     * Rewrites that need no pattern at all.
     *
     * strtr() walks the subject once for the whole set, which is strictly
     * better than one str_replace() pass per entry.
     */
    private const array LITERALS = [
        '&nbsp;' => ' ',
        '<br>' => "\n",
        '<br/>' => "\n",
        '<br />' => "\n",
        '<BR>' => "\n",
        // Block tags are handled here rather than by a pattern: an editor emits
        // them without attributes almost every time, and a literal swap costs a
        // third of what the equivalent regex does. The pattern below still
        // catches the ones that do carry attributes.
        '<p>' => "\n\n",
        '</p>' => "\n\n",
        '<div>' => "\n\n",
        '</div>' => "\n\n",
    ];

    /** How an <a> is rendered when the label already is the URL. */
    private const string LINK_APPEND = 'append';

    /**
     * The WhatsApp marker each inline tag becomes.
     *
     * One pattern with a backreference handles all six, so six passes over the
     * body become one.
     */
    private const array MARKERS = [
        'b' => '*',
        'strong' => '*',
        'i' => '_',
        'em' => '_',
        's' => '~',
        'del' => '~',
        'strike' => '~',
    ];

    private const string INLINE_PATTERN = '/<(b|strong|i|em|s|del|strike)\b[^>]*>(.*?)<\/\1>/is';

    /**
     * Sentinels standing in for a marker until its final position is known.
     *
     * A marker cannot be written where the tag sits, because WhatsApp renders
     * one only on a word boundary and the tag may well close mid-word. Private
     * use codepoints survive strip_tags() and cannot collide with a body.
     */
    private const string OPEN = "\u{E010}";

    private const string CLOSE = "\u{E011}";

    /**
     * Characters that may sit against a marker on its outer side without
     * WhatsApp giving up on the pair.
     */
    private const string AFTER_CLOSE = ".,;:!?)]}\"'»";

    private const string BEFORE_OPEN = "([{\"'«";

    public function __construct(
        private readonly Memo $memo = new Memo,
        private readonly string $linkStyle = self::LINK_APPEND,
    ) {}

    /**
     * Convert a body to WhatsApp markup. Never URL-encodes.
     */
    public function format(?string $source): string
    {
        if ($source === null) {
            return '';
        }

        // The overwhelmingly common body is plain text typed into a field. It
        // can skip every pattern in this class.
        if (! str_contains($source, '<') && ! str_contains($source, '&')) {
            return trim($source);
        }

        return $this->memo->remember($source, fn (): string => $this->convert($source));
    }

    private function convert(string $html): string
    {
        // Every block below is guarded by a substring test. Scanning for a
        // literal is far cheaper than running a pattern that will not match,
        // and a typical body contains two or three of these constructs, not
        // all of them.
        $protected = [];

        if (str_contains($html, '<pre')) {
            $html = $this->protect($html, '/<pre(?:>|\s[^>]*>)(.*?)<\/pre>/is', '```%s```', $protected);
        }

        if (str_contains($html, '<code')) {
            $html = $this->protect($html, '/<code(?:>|\s[^>]*>)(.*?)<\/code>/is', '```%s```', $protected);
        }

        $html = strtr($html, self::LITERALS);

        if (str_contains($html, '<blockquote')) {
            $html = $this->blockquotes($html);
        }

        if (str_contains($html, '<ul') || str_contains($html, '<ol')) {
            $html = $this->lists($html);
        }

        $html = (string) preg_replace_callback(
            self::INLINE_PATTERN,
            static fn (array $m): string => self::wrap($m[2], self::MARKERS[strtolower($m[1])]),
            $html,
        );

        if (str_contains($html, '<a')) {
            $html = (string) preg_replace_callback(
                '/<a\b[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is',
                fn (array $m): string => $this->link($m[1], $m[2]),
                $html,
            );
        }

        // Whatever survived the literal swap: block tags carrying attributes.
        if (str_contains($html, '<p ') || str_contains($html, '<div ')) {
            $html = (string) preg_replace('/<\/?(?:p|div)\b[^>]*>/i', "\n\n", $html);
        }

        if (str_contains($html, '<')) {
            $html = strip_tags($html);
        }

        // Entities are decoded last, after the tags are gone. Decoding earlier
        // would turn an escaped "&lt;b&gt;" in the source into a real tag and
        // format it; not decoding at all — which is what version 1 did — leaks
        // "&amp;" and "&egrave;" straight into the chat window.
        if (str_contains($html, '&')) {
            $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $html = $this->snap($this->collapse($html));

        // Restored after collapsing, so that indentation inside a code block
        // survives exactly as the author wrote it.
        return $protected === []
            ? $html
            : trim((string) preg_replace('/\n{3,}/', "\n\n", strtr($html, $protected)));
    }

    /**
     * Replace matches with opaque placeholders, remembering their final form.
     *
     * @param  array<string, string>  $protected
     */
    private function protect(string $html, string $pattern, string $format, array &$protected): string
    {
        return (string) preg_replace_callback(
            $pattern,
            static function (array $m) use (&$protected, $format): string {
                // A private-use codepoint, not a NUL byte: strip_tags() eats
                // NULs, which would silently destroy the placeholder.
                $key = "\u{E000}wt".count($protected)."\u{E000}";

                $protected[$key] = sprintf($format, trim(html_entity_decode(
                    strip_tags($m[1]),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8',
                )));

                return $key;
            },
            $html,
        );
    }

    private function blockquotes(string $html): string
    {
        // A quoted paragraph arrives wrapped twice; unwrap before prefixing so
        // the "> " lands on the text and not on an empty line.
        return (string) preg_replace_callback(
            '/<blockquote(?:>|\s[^>]*>)(.*?)<\/blockquote>/is',
            static function (array $m): string {
                $lines = preg_split('/\n\s*/', trim(strip_tags($m[1]))) ?: [];

                return '> '.implode("\n> ", $lines)."\n\n";
            },
            $html,
        );
    }

    private function lists(string $html): string
    {
        $html = (string) preg_replace_callback(
            '/<ul(?:>|\s[^>]*>)(.*?)<\/ul>/is',
            static fn (array $m): string => self::items($m[1], static fn (int $i, string $text): string => '- '.$text),
            $html,
        );

        return (string) preg_replace_callback(
            '/<ol(?:>|\s[^>]*>)(.*?)<\/ol>/is',
            // The counter is created per list. Version 1 shared one across the
            // whole message, so a second list carried on numbering from the
            // first.
            static fn (array $m): string => self::items($m[1], static fn (int $i, string $text): string => $i.'. '.$text),
            $html,
        );
    }

    /**
     * @param  callable(int, string): string  $render
     */
    private static function items(string $inner, callable $render): string
    {
        $index = 0;

        $rendered = preg_replace_callback(
            '/<li(?:>|\s[^>]*>)(.*?)<\/li>/is',
            static function (array $m) use (&$index, $render): string {
                return $render(++$index, trim($m[1]))."\n";
            },
            $inner,
        );

        return "\n".trim((string) $rendered)."\n\n";
    }

    /**
     * WhatsApp markers only take effect when they hug the text, so the inner
     * value is trimmed — but the whitespace trimming removes is put back
     * outside the pair. Dropping it welded the span to whatever followed,
     * which is how "commodo. Non" reached the chat as "commodo._Non".
     *
     * The marker itself is written as a sentinel; snap() decides where it
     * finally lands.
     */
    private static function wrap(string $text, string $marker): string
    {
        $text = strip_tags($text);

        if (preg_match('/^(\\s*)(.*?)(\\s*)$/us', $text, $parts) !== 1) {
            return $text;
        }

        [, $lead, $core, $trail] = $parts;

        if ($core === '') {
            return $text;
        }

        return $lead.self::OPEN.$marker.$core.self::CLOSE.$marker.$trail;
    }

    /**
     * Move every marker onto a word boundary, then write it out.
     *
     * An editor is happy to style half a word; WhatsApp is not. A marker with
     * a letter on its outer side is printed verbatim — the reader sees
     * "C*ommodo cill*um" — so a pair that cuts into a word is widened to cover
     * the whole of it. Widening rather than dropping keeps the intent of
     * someone who selected "Commodo cill" and pressed bold.
     */
    private function snap(string $text): string
    {
        if (! str_contains($text, self::OPEN)) {
            return $text;
        }

        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $plain = [];

        /** @var list<array{int, string, string}> $markers */
        $markers = [];

        $skip = false;

        foreach ($characters as $index => $character) {
            if ($skip) {
                $skip = false;

                continue;
            }

            if ($character === self::OPEN || $character === self::CLOSE) {
                // The character after a sentinel is the marker it carries.
                $markers[] = [count($plain), $character, $characters[$index + 1] ?? ''];

                $skip = true;

                continue;
            }

            $plain[] = $character;
        }

        $last = count($plain);

        foreach ($markers as $position => [$at, $kind, $marker]) {
            if ($kind === self::OPEN) {
                while ($at > 0 && self::welds($plain[$at - 1]) && ! str_contains(self::BEFORE_OPEN, $plain[$at - 1])) {
                    $at--;
                }
            } else {
                while ($at < $last && self::welds($plain[$at]) && ! str_contains(self::AFTER_CLOSE, $plain[$at])) {
                    $at++;
                }
            }

            $markers[$position] = [$at, $kind, $marker];
        }

        // Written back to front so an earlier insertion cannot shift the index
        // of a later one.
        usort($markers, static fn (array $first, array $second): int => $second[0] <=> $first[0]);

        foreach ($markers as [$at, $kind, $marker]) {
            array_splice($plain, $at, 0, [$marker]);
        }

        return implode('', $plain);
    }

    /**
     * Whether a character welds to a marker placed beside it, which is what
     * makes WhatsApp refuse the pair.
     */
    private static function welds(string $character): bool
    {
        return preg_match('/\\s/u', $character) !== 1;
    }

    private function link(string $href, string $label): string
    {
        $label = trim(strip_tags($label));
        $href = trim($href);

        if ($href === '' || $label === $href || $this->linkStyle === 'strip') {
            return $label !== '' ? $label : $href;
        }

        // Losing the href — which is what strip_tags() alone does — turns
        // "click here" into a dead end. Appending keeps the link usable, and
        // WhatsApp linkifies a bare URL on its own.
        return $label === '' ? $href : "{$label} ({$href})";
    }

    /**
     * Squeeze runs of blank lines and trailing spaces without touching the
     * single newlines that carry meaning inside lists and quotes.
     */
    private function collapse(string $text): string
    {
        // Ordered so that the cheap patterns do most of the work: collapsing a
        // run of two or more spaces touches far fewer positions than matching
        // every single one.
        return trim((string) preg_replace(
            ['/[ \t]*\n/', '/[ \t]{2,}/', '/\n{3,}/'],
            ["\n", ' ', "\n\n"],
            $text,
        ));
    }
}
