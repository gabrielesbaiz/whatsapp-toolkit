<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Support;

/**
 * A bounded, per-request memo.
 *
 * Formatting the same body over and over is the normal case, not the odd one:
 * an admin index screen renders one chat link per row from a single template,
 * and a batch send fills the same body for every recipient. Nothing here
 * survives the request, so there is no cache to invalidate and no staleness to
 * reason about — it only removes work that has already been done once.
 */
final class Memo
{
    /** @var array<string, string> */
    private array $entries = [];

    public function __construct(
        private readonly int $size = 128,
        private readonly bool $enabled = true,
    ) {}

    /**
     * Return the memoized value, computing it on first sight.
     *
     * @param  callable(): string  $compute
     */
    public function remember(string $subject, callable $compute): string
    {
        if (! $this->enabled) {
            return $compute();
        }

        // hash() on xxh3 is both faster than the string comparison it replaces
        // and bounded in length, which keeps the key array small when bodies
        // are long.
        $key = hash('xxh3', $subject);

        if (isset($this->entries[$key])) {
            return $this->entries[$key];
        }

        if (count($this->entries) >= $this->size) {
            // Cheapest possible eviction: drop the oldest insertion. The access
            // pattern here is "the same handful of bodies, repeatedly", so LRU
            // bookkeeping would cost more than it saves.
            array_shift($this->entries);
        }

        return $this->entries[$key] = $compute();
    }

    public function flush(): void
    {
        $this->entries = [];
    }

    public function count(): int
    {
        return count($this->entries);
    }
}
