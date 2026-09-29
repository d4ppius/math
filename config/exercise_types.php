<?php

use App\Services\ExerciseTypes\AdditionExerciseType;
use App\Services\ExerciseTypes\MultiplicationExerciseType;
use App\Services\ExerciseTypes\SubtractionExerciseType;

return [
    'multiplication' => MultiplicationExerciseType::class,
    'addition' => AdditionExerciseType::class,
    'subtraction' => SubtractionExerciseType::class,
];
