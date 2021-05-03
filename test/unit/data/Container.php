<?php
declare(strict_types=1);

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{

    public function get(string $id)
    {
        $mock = 1;
        return $mock;
    }

    public function has(string $id): bool
    {
        return true;
    }
}