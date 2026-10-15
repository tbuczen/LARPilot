<?php

namespace App\Domain\CallTranscript\Service\Import;

use App\Domain\CallTranscript\DTO\ImportedCallRecording;

interface CallRecordingImporterInterface
{
    public function import(string $archivePath, string $targetDirectory): ImportedCallRecording;
}
