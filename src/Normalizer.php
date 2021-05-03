<?php
declare(strict_types=1);

namespace PTS\DataTransformer;

class Normalizer implements NormalizerInterface
{

    public function normalize(array $rules): array
    {
        $map = [
            'pipe-populate' => [],
            'pipe-extract' => [],
            'rules' => $rules,
            'refs' => [],
        ];

        foreach ($map['rules'] as $name => &$rule) {
            $rule['prop'] ??= $name;
            $map['refs'][$name] = $rule['ref'] ?? null;
            $map['pipe-populate'][$name] = $rule['pipe-populate'] ?? null;
            $map['pipe-extract'][$name] = $rule['pipe-extract'] ?? null;
        }

        $map['refs'] = array_filter($map['refs']);
        $map['pipe-populate'] = array_filter($map['pipe-populate']);
        $map['pipe-extract'] = array_filter($map['pipe-extract']);

        return $map;
    }
}
