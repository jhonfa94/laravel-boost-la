<?php

declare(strict_types=1);

namespace App\ViewModels;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

/**
 * @implements Arrayable<string, mixed>
 */
abstract class ViewModel implements Arrayable
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return collect((new ReflectionClass($this))->getMethods(ReflectionMethod::IS_PUBLIC))
            ->reject(fn (ReflectionMethod $method): bool => in_array($method->getName(), ['__construct', 'toArray'], true))
            ->mapWithKeys(fn (ReflectionMethod $method): array => [
                Str::camel($method->getName()) => $this->{$method->getName()}(),
            ])
            ->all();
    }
}
