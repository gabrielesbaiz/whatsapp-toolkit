<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Support;

use Gabrielesbaiz\WhatsappToolkit\Exceptions\MessageTooLongException;
use Stringable;

/**
 * A message body once it is already WhatsApp markup.
 *
 * Formatting and encoding are separate steps in this package, and this object
 * is the boundary between them: it holds plain text that a Cloud API request
 * can send verbatim, and knows how to encode itself when a link needs it.
 */
final readonly class Message implements Stringable
{
    public function __construct(public string $text) {}

    public function __toString(): string
    {
        return $this->text;
    }

    public static function empty(): self
    {
        return new self('');
    }

    public function isEmpty(): bool
    {
        return $this->text === '';
    }

    /**
     * Character length, counted the way WhatsApp counts it.
     *
     * strlen() would report bytes, and an accented Italian body would look a
     * third longer than it is.
     */
    public function length(): int
    {
        return mb_strlen($this->text);
    }

    /**
     * Cut to a maximum length at a word boundary, with an ellipsis.
     */
    public function truncate(int $limit, string $ellipsis = '…'): self
    {
        if ($limit <= 0 || $this->length() <= $limit) {
            return $this;
        }

        $room = max(1, $limit - mb_strlen($ellipsis));
        $cut = mb_substr($this->text, 0, $room);

        $lastSpace = mb_strrpos($cut, ' ');

        // Only honour the word boundary when it is not so far back that the
        // message loses most of its content.
        if ($lastSpace !== false && $lastSpace > (int) ($room * 0.6)) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return new self(rtrim($cut).$ellipsis);
    }

    /**
     * Enforce a limit, either by cutting or by refusing.
     *
     * @param  'truncate'|'throw'|'ignore'  $strategy
     *
     * @throws MessageTooLongException
     */
    public function enforce(int $limit, string $strategy): self
    {
        if ($limit <= 0 || $this->length() <= $limit || $strategy === 'ignore') {
            return $this;
        }

        if ($strategy === 'throw') {
            throw MessageTooLongException::make($this->length(), $limit);
        }

        return $this->truncate($limit);
    }

    /**
     * Percent-encode for use in a click-to-chat query string.
     *
     * rawurlencode(), not urlencode(): the latter writes a space as "+", which
     * some WhatsApp clients render literally in the prefilled body.
     */
    public function encoded(): string
    {
        return rawurlencode($this->text);
    }
}
