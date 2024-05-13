<?php

declare(strict_types=1);

test('no usage of dump or dd')
    ->expect(['dump', 'dd'])
    ->not
    ->toBeUsed();

test('strict types declaration')
    ->expect('XbNz\Gemini')
    ->toUseStrictTypes();
