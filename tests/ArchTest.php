<?php

declare(strict_types=1);

test('no usage of dump or dd')
    ->expect(['dump', 'dd'])
    ->not
    ->toBeUsed();

test('strict types declaration')
    ->expect('XbNz\Gemini\OAuth2\Saloon\Requests')
    ->toUseStrictTypes();

// test('classes are final')
//    ->expect('XbNz\Gemini')
//    ->toBeFinal();
