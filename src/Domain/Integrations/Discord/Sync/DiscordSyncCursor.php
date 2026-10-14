<?php

namespace App\Domain\Integrations\Discord\Sync;

final readonly class DiscordSyncCursor
{
    public const TIMESTAMP_FORMAT = 'Y-m-d\\TH:i:s.uP';

    public function __construct(
        public ?\DateTimeImmutable $archivedThrough = null,
        public ?\DateTimeImmutable $backfillBefore = null,
        public ?\DateTimeImmutable $backfillNewest = null,
    ) {
    }

    /**
     * @param array<mixed>|null $cursor
     */
    public static function fromArray(?array $cursor): self
    {
        if ($cursor === null) {
            return new self();
        }

        return new self(
            self::readTimestamp($cursor, 'archivedThrough'),
            self::readTimestamp($cursor, 'backfillBefore'),
            self::readTimestamp($cursor, 'backfillNewest'),
        );
    }

    public function isBackfilling(): bool
    {
        return $this->backfillBefore !== null;
    }

    public function continueBackfill(\DateTimeImmutable $backfillBefore, ?\DateTimeImmutable $newestArchived): self
    {
        return new self($this->archivedThrough, $backfillBefore, self::latest($this->backfillNewest, $newestArchived));
    }

    public function completeBackfill(?\DateTimeImmutable $newestArchived): self
    {
        return new self(self::latest(self::latest($this->archivedThrough, $this->backfillNewest), $newestArchived));
    }

    /**
     * @return array<string, ?string>
     */
    public function toArray(): array
    {
        return [
            'archivedThrough' => $this->archivedThrough?->format(self::TIMESTAMP_FORMAT),
            'backfillBefore' => $this->backfillBefore?->format(self::TIMESTAMP_FORMAT),
            'backfillNewest' => $this->backfillNewest?->format(self::TIMESTAMP_FORMAT),
        ];
    }

    public static function latest(?\DateTimeImmutable $left, ?\DateTimeImmutable $right): ?\DateTimeImmutable
    {
        if ($left === null) {
            return $right;
        }

        if ($right === null) {
            return $left;
        }

        return max($left, $right);
    }

    /**
     * @param array<mixed> $cursor
     */
    private static function readTimestamp(array $cursor, string $key): ?\DateTimeImmutable
    {
        $timestamp = $cursor[$key] ?? null;
        if (!is_string($timestamp) || $timestamp === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($timestamp);
        } catch (\Exception) {
            return null;
        }
    }
}
