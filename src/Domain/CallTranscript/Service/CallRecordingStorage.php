<?php

namespace App\Domain\CallTranscript\Service;

use App\Domain\CallTranscript\Entity\CallRecording;
use Symfony\Component\Filesystem\Filesystem;

final readonly class CallRecordingStorage
{
    public function __construct(
        private string $storageDirectory,
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function directoryFor(CallRecording $recording): string
    {
        $directory = $this->storageDirectory . '/' . $recording->getId()->toRfc4122();
        $this->filesystem->mkdir($directory, 0700);

        return $directory;
    }

    public function pathFor(CallRecording $recording, string $filename): string
    {
        return $this->directoryFor($recording) . '/' . basename($filename);
    }

    public function removeFile(CallRecording $recording, ?string $filename): void
    {
        if ($filename === null) {
            return;
        }

        $this->filesystem->remove($this->pathFor($recording, $filename));
    }

    public function removeAll(CallRecording $recording): void
    {
        $this->filesystem->remove($this->recordingDirectory($recording));
    }

    public function recordingDirectory(CallRecording $recording): string
    {
        return $this->storageDirectory . '/' . $recording->getId()->toRfc4122();
    }
}
