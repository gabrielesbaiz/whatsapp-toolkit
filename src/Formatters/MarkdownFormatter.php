<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Formatters;

use Gabrielesbaiz\WhatsappToolkit\Contracts\MessageFormatter;
use Gabrielesbaiz\WhatsappToolkit\Support\Memo;

/**
 * Markdown converted to WhatsApp markup.
 *
 * The two notations are close enough to be confused and different enough to
 * look broken when they are: Markdown's **bold** is WhatsApp's *bold*, and
 * Markdown's *italic* is WhatsApp's _italic_, so pasting one into the other
 * produces asterisks on screen. Config-stored message templates are usually
 * written in Markdown, which is why this exists.
 */
final class MarkdownFormatter implements MessageFormatter
{
    /**
     * Bold is converted to a placeholder rather than straight to an asterisk.
     *
     * The rules are applied in sequence, each seeing the previous one's output,
     * so a "*bold*" produced by the first rule would immediately be claimed by
     * the italic rule and come out as "_bold_". The placeholder makes the two
     * passes independent; it is swapped back for the real marker at the end.
     *
     * @var array<string, string>
     */
    private const array RULES = [
        '/(?<!\*)\*\*(?!\s)(.+?)(?<!\s)\*\*(?!\*)/s' => self::BOLD.'$1'.self::BOLD,
        '/(?<!_)__(?!\s)(.+?)(?<!\s)__(?!_)/s' => self::BOLD.'$1'.self::BOLD,
        '/~~(?!\s)(.+?)(?<!\s)~~/s' => self::STRIKE.'$1'.self::STRIKE,
        '/(?<![\*\w])\*(?!\s|\*)(.+?)(?<!\s)\*(?![\*\w])/s' => self::ITALIC.'$1'.self::ITALIC,
        '/(?<![_\w])_(?!\s|_)(.+?)(?<!\s)_(?![_\w])/s' => self::ITALIC.'$1'.self::ITALIC,
        '/(?<!`)`(?!`)(.+?)(?<!`)`(?!`)/s' => '```$1```',
        '/^#{1,6}\s*(.+?)\s*$/m' => self::BOLD.'$1'.self::BOLD,
        '/^\s*[\*\+]\s+/m' => '- ',
        '/!\[[^\]]*\]\(([^)\s]+)[^)]*\)/' => '$1',
        '/\[([^\]]+)\]\(([^)\s]+)[^)]*\)/' => '$1 ($2)',
    ];

    private const string BOLD = "\u{E010}";

    private const string ITALIC = "\u{E011}";

    private const string STRIKE = "\u{E012}";

    /** @var array<string, string> */
    private const array MARKERS = [
        self::BOLD => '*',
        self::ITALIC => '_',
        self::STRIKE => '~',
    ];

    public function __construct(private readonly Memo $memo = new Memo) {}

    public function format(?string $source): string
    {
        if ($source === null || trim($source) === '') {
            return '';
        }

        return $this->memo->remember('md:'.$source, static function () use ($source): string {
            $text = preg_replace(
                array_keys(self::RULES),
                array_values(self::RULES),
                $source,
            );

            $text = strtr((string) $text, self::MARKERS);

            $text = (string) preg_replace('/\n{3,}/', "\n\n", $text);

            return trim($text);
        });
    }
}
