<?php
declare(strict_types=1);

namespace PTS\DataTransformer;

interface NormalizerInterface
{
    public function normalize(array $rules): array;
}
