<?php

declare(strict_types=1);

namespace Tests\Unit\CallTranscript;

use App\Domain\CallTranscript\DTO\ImportedCallRecording;
use App\Domain\CallTranscript\DTO\ImportedCallTrack;
use App\Domain\CallTranscript\Service\Import\CallRecordingImportException;
use App\Domain\CallTranscript\Service\Import\CraigInfoParser;
use App\Domain\CallTranscript\Service\Import\CraigRecordingImporter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Support\CallTranscript\CraigArchiveBuilder;

class CraigRecordingImporterTest extends TestCase
{
    private string $targetDirectory;

    /**
     * @var list<string>
     */
    private array $archives = [];

    protected function setUp(): void
    {
        $this->targetDirectory = sys_get_temp_dir() . '/craig_import_' . bin2hex(random_bytes(6));
        mkdir($this->targetDirectory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove([$this->targetDirectory, ...$this->archives]);
    }

    public function testImportsOneTrackPerSpeakerAndStripsTrackNumbers(): void
    {
        $recording = $this->import([
            '1-anna.flac' => 'anna audio',
            '2-tomek.ogg' => 'tomek audio',
            'raw.dat' => 'ignored',
            'README.md' => 'ignored',
        ]);

        $this->assertSame(['anna', 'tomek'], $this->speakers($recording->tracks));
        $this->assertNull($recording->startedAt);
        $this->assertSame([], $recording->notes);
        foreach ($recording->tracks as $track) {
            $this->assertNotNull($track->audioFilename);
            $this->assertNull($track->transcriptFilename);
            $this->assertFileExists($this->targetDirectory . '/' . $track->audioFilename);
        }

        $this->assertCount(2, glob($this->targetDirectory . '/*') ?: []);
    }

    public function testKeepsNamesWithoutTrackNumberAndIgnoresHiddenAndNestedJunk(): void
    {
        $recording = $this->import([
            'craig/Anna-Maria.flac' => 'audio',
            '__MACOSX/craig/._Anna-Maria.flac' => 'junk',
            '.DS_Store' => 'junk',
        ]);

        $this->assertSame(['Anna-Maria'], $this->speakers($recording->tracks));
    }

    public function testReadsStartTimeAndNotesFromInfoFile(): void
    {
        $recording = $this->import([
            '1-anna.flac' => 'audio',
            'info.txt' => CraigArchiveBuilder::info(),
        ]);

        $this->assertSame('2026-10-01T18:00:00+00:00', $recording->startedAt?->format(DATE_ATOM));
        $this->assertCount(1, $recording->notes);
        $this->assertSame(4.5, $recording->notes[0]->startSeconds);
        $this->assertSame('decision: Faction X betrays the king', $recording->notes[0]->text);
        $this->assertSame(['anna'], $this->speakers($recording->tracks));
    }

    public function testAttachesCraigTranscriptsToMatchingSpeakersPreferringVtt(): void
    {
        $recording = $this->import([
            '1-anna.flac' => 'audio',
            '1-anna.txt' => 'plain',
            '1-anna.vtt' => "WEBVTT\n\n00:01.000 --> 00:02.000\nHej",
            '2-tomek.flac' => 'audio',
            '3-ghost.srt' => "1\n00:00:01,000 --> 00:00:02,000\nBoo",
        ]);

        $tracksBySpeaker = [];
        foreach ($recording->tracks as $track) {
            $tracksBySpeaker[$track->speaker] = $track;
        }

        $this->assertSame(['anna', 'ghost', 'tomek'], $this->speakers($recording->tracks));
        $this->assertStringEndsWith('.vtt', (string) $tracksBySpeaker['anna']->transcriptFilename);
        $this->assertStringStartsWith('WEBVTT', (string) file_get_contents($this->targetDirectory . '/' . $tracksBySpeaker['anna']->transcriptFilename));
        $this->assertNull($tracksBySpeaker['tomek']->transcriptFilename);
        $this->assertNull($tracksBySpeaker['ghost']->audioFilename);
        $this->assertStringEndsWith('.srt', (string) $tracksBySpeaker['ghost']->transcriptFilename);
    }

    public function testReadsDiscordUserIdsOfTracksFromInfoFile(): void
    {
        $info = "Tracks:\n\tAnna#0 (11)\n\ttomek (12)\n\nNotes:\n";
        $recording = $this->import([
            '1-anna.flac' => 'audio',
            '2-tomek_0.flac' => 'audio',
            '3-ewa.flac' => 'audio',
            'info.txt' => $info,
        ]);

        $this->assertSame(['11', null, '12'], array_map(static fn (ImportedCallTrack $track): ?string => $track->discordUserId, $recording->tracks));
        $this->assertSame(['anna' => '11', 'tomek' => '12'], (new CraigInfoParser())->parseTrackUserIds($info));
    }

    public function testRejectsArchiveWithoutSpeakers(): void
    {
        $this->expectException(CallRecordingImportException::class);

        $this->import(['info.txt' => CraigArchiveBuilder::info(), 'notes.md' => 'nothing']);
    }

    public function testRejectsFileThatIsNotZip(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'craig_');
        file_put_contents($path, 'not a zip');
        $this->archives[] = $path;

        $this->expectException(CallRecordingImportException::class);

        $this->importer()->import($path, $this->targetDirectory);
    }

    /**
     * @param array<string, string> $entries
     */
    private function import(array $entries): ImportedCallRecording
    {
        $archive = CraigArchiveBuilder::build($entries);
        $this->archives[] = $archive;

        return $this->importer()->import($archive, $this->targetDirectory);
    }

    private function importer(): CraigRecordingImporter
    {
        return new CraigRecordingImporter(new CraigInfoParser());
    }

    /**
     * @param list<ImportedCallTrack> $tracks
     *
     * @return list<string>
     */
    private function speakers(array $tracks): array
    {
        return array_map(static fn (ImportedCallTrack $track): string => $track->speaker, $tracks);
    }
}
