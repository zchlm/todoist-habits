<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\Models\Habit::class, function (Faker\Generator $faker) {
    return [
        't_id' => $faker->randomNumber(8),
        'content' => "[day {$faker->randomNumber(1)}]",
        'date_string' => 'ev workday 8:30pm',
        'due_date' => $faker->date('D j M Y H:i:s O')
    ];
});
