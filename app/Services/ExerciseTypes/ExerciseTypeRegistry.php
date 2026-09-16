<?php

namespace App\Services\ExerciseTypes;

use App\Contracts\ExerciseTypeContract;
use InvalidArgumentException;

class ExerciseTypeRegistry
{
    /** @var array<string, ExerciseTypeContract> */
    private array $instances = [];

    public function get(string $key): ExerciseTypeContract
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        $class = config("exercise_types.{$key}");

        if (! $class) {
            throw new InvalidArgumentException("Unknown exercise type [{$key}].");
        }

        return $this->instances[$key] = app($class);
    }

    /** @return list<ExerciseTypeContract> */
    public function all(): array
    {
        return array_map(
            fn (string $key) => $this->get($key),
            array_keys(config('exercise_types', []))
        );
    }
}
