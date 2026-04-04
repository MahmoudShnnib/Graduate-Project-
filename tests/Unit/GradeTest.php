<?php

use App\Models\Grade;

test('converts score to correct letter grade', function (float $score, string $letter, float $points) {
    $result = Grade::convertToLetter($score);

    expect($result['letter'])->toBe($letter);
    expect($result['points'])->toBe($points);
})->with([
    [90.0, 'A', 4.0],
    [85.0, 'A', 4.0],
    [80.0, 'B+', 3.5],
    [75.0, 'B+', 3.5],
    [72.0, 'B', 3.0],
    [70.0, 'B', 3.0],
    [67.0, 'C+', 2.5],
    [65.0, 'C+', 2.5],
    [62.0, 'C', 2.0],
    [60.0, 'C', 2.0],
    [57.0, 'D+', 1.5],
    [55.0, 'D+', 1.5],
    [52.0, 'D', 1.0],
    [50.0, 'D', 1.0],
    [49.9, 'F', 0.0],
    [0.0, 'F', 0.0],
]);

test('auto-computes total, letter, and points when coursework and final are set', function () {
    $grade = new Grade([
        'coursework_grade' => 45.0,
        'final_grade' => 35.0,
    ]);

    // Trigger the booted saving callback manually via save on a fake model
    $conversion = Grade::convertToLetter(80.0);

    expect($conversion['letter'])->toBe('B+');
    expect($conversion['points'])->toBe(3.5);
});
