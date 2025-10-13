<?php

namespace App\Domain\Core\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class JsonToArrayTransformer implements DataTransformerInterface
{
    /**
     * Transforms an array to a JSON string (for displaying in the form)
     */
    public function transform(mixed $value): string
    {
        if (null === $value || [] === $value) {
            return '';
        }

        if (!is_array($value)) {
            return '';
        }

        return json_encode($value);
    }

    /**
     * Transforms a JSON string back to an array (when submitting the form)
     */
    public function reverseTransform(mixed $value): array
    {
        if (empty($value)) {
            return [];
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new TransformationFailedException(
                sprintf('Invalid JSON: %s', json_last_error_msg())
            );
        }

        return $decoded ?? [];
    }
}
