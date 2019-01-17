<?php
declare(strict_types=1);

namespace PTS\DataTransformer;


class Normalizer implements NormalizerInterface
{

    public function normalize(array $rules): array
    {
        $map = [
            'pipe' => [],
            'rules' => $rules,
            'refs' => [],
        ];

        foreach ($map['rules'] as $name => &$rule) {
            $rule['prop'] = $rule['prop'] ?? $name;
            $map['refs'][$name] = $rule['ref'] ?? null;
            $map['pipe'][$name] = $rule['pipe'] ?? null;
        }

        $map['refs'] = array_filter($map['refs']);
        $map['pipe'] = array_filter($map['pipe']);

        return $map;
    }
}
