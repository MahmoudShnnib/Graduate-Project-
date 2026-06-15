<?php

it('renders the custom error page', function (string $code, string $expected) {
    $html = view("errors.{$code}")->render();

    expect($html)->toContain($code)
        ->and($html)->toContain($expected)
        ->and($html)->toContain('dir="rtl"');
})->with([
    '419' => ['419', 'انتهت صلاحية الجلسة'],
    '429' => ['429', 'طلبات كثيرة جداً'],
    '503' => ['503', 'الخدمة غير متاحة مؤقتاً'],
]);
