<?php

declare(strict_types=1);

namespace Tests\Support\CallTranscript;

use RuntimeException;
use ZipArchive;

final class CraigArchiveBuilder
{
    /**
     * @param array<string, string> $entries
     */
    public static function build(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'craig_') . '.zip';
        $archive = new ZipArchive();
        if ($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create test archive.');
        }

        foreach ($entries as $name => $content) {
            $archive->addFromString($name, $content);
        }

        $archive->close();

        return $path;
    }

    public static function info(): string
    {
        return <<<'INFO'
            Recording abc123

            Guild:		Test Guild (1)
            Channel:	Planning (2)
            Requester:	organizer (3)
            Start time:	2026-10-01T18:00:00.000Z

            Tracks:
            	anna (11)
            	tomek (12)

            Notes:
            	0:00:04.50: decision: Faction X betrays the king
            INFO;
    }
}
