<?php

namespace App\Domain\CallTranscript\Service\SpeechToText;

use App\Domain\CallTranscript\DTO\TranscriptSegment;
use OpenAI\Client;
use OpenAI\Factory;
use Throwable;

final class OpenAISpeechToTextProvider implements SpeechToTextProviderInterface
{
    public const MAX_UPLOAD_BYTES = 25 * 1024 * 1024;

    private ?Client $client = null;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'whisper-1',
    ) {
    }

    public function transcribe(string $audioPath, ?string $language): array
    {
        $bytes = @filesize($audioPath);
        if ($bytes === false) {
            throw SpeechToTextException::unreadableFile();
        }

        if ($bytes > self::MAX_UPLOAD_BYTES) {
            throw SpeechToTextException::fileTooLarge($bytes, self::MAX_UPLOAD_BYTES);
        }

        $audio = fopen($audioPath, 'rb');
        if ($audio === false) {
            throw SpeechToTextException::unreadableFile();
        }

        $parameters = [
            'model' => $this->model,
            'file' => $audio,
            'response_format' => 'verbose_json',
            'timestamp_granularities' => ['segment'],
        ];
        if ($language !== null) {
            $parameters['language'] = $language;
        }

        try {
            $response = $this->client()->audio()->transcribe($parameters);
        } catch (Throwable $failure) {
            throw SpeechToTextException::providerFailed($failure);
        } finally {
            if (is_resource($audio)) {
                fclose($audio);
            }
        }

        if ($response->segments === []) {
            $text = trim($response->text);

            return $text === '' ? [] : [new TranscriptSegment(0.0, $text)];
        }

        $segments = [];
        foreach ($response->segments as $segment) {
            $text = trim($segment->text);
            if ($text === '') {
                continue;
            }

            $segments[] = new TranscriptSegment($segment->start, $text);
        }

        return $segments;
    }

    private function client(): Client
    {
        return $this->client ??= (new Factory())->withApiKey($this->apiKey)->make();
    }
}
