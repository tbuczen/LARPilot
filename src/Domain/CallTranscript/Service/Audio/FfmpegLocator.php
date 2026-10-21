<?php

namespace App\Domain\CallTranscript\Service\Audio;

use Symfony\Component\Process\ExecutableFinder;

class FfmpegLocator
{
    private ?string $binary = null;

    private bool $resolved = false;

    public function __construct(
        private readonly bool $enabled = true,
    ) {
    }

    public function find(): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        if (!$this->resolved) {
            $this->binary = (new ExecutableFinder())->find('ffmpeg');
            $this->resolved = true;
        }

        return $this->binary;
    }

    public function isAvailable(): bool
    {
        return $this->find() !== null;
    }
}
