<?php

declare(strict_types=1);

use App\Services\BotRateLimiter;
use Illuminate\Support\Facades\RateLimiter;

test('key method generates correct rate limit key', function () {
    $chatId = 123456;
    $command = 'test';

    $key = BotRateLimiter::key($chatId, $command);

    expect($key)->toBe('bot_command:123456:test');
});

test('attempt method allows requests within rate limit', function () {
    $rateLimiter = new BotRateLimiter;
    $key = 'test_key_'.uniqid();

    // First 10 attempts should succeed for medium tier (10 req/min)
    for ($i = 0; $i < 10; $i++) {
        $result = $rateLimiter->attempt($key, 'medium');
        expect($result)->toBeTrue();
    }

    // 11th attempt should fail
    $result = $rateLimiter->attempt($key, 'medium');
    expect($result)->toBeFalse();

    // Clean up
    RateLimiter::clear($key);
});

test('attempt method respects different tier limits', function () {
    $rateLimiter = new BotRateLimiter;

    // Test heavy tier (5 req/min)
    $heavyKey = 'heavy_'.uniqid();
    for ($i = 0; $i < 5; $i++) {
        expect($rateLimiter->attempt($heavyKey, 'heavy'))->toBeTrue();
    }
    expect($rateLimiter->attempt($heavyKey, 'heavy'))->toBeFalse();
    RateLimiter::clear($heavyKey);

    // Test medium tier (10 req/min)
    $mediumKey = 'medium_'.uniqid();
    for ($i = 0; $i < 10; $i++) {
        expect($rateLimiter->attempt($mediumKey, 'medium'))->toBeTrue();
    }
    expect($rateLimiter->attempt($mediumKey, 'medium'))->toBeFalse();
    RateLimiter::clear($mediumKey);

    // Test light tier (20 req/min)
    $lightKey = 'light_'.uniqid();
    for ($i = 0; $i < 20; $i++) {
        expect($rateLimiter->attempt($lightKey, 'light'))->toBeTrue();
    }
    expect($rateLimiter->attempt($lightKey, 'light'))->toBeFalse();
    RateLimiter::clear($lightKey);
});

test('remaining method returns correct number of remaining attempts', function () {
    $rateLimiter = new BotRateLimiter;
    $key = 'remaining_test_'.uniqid();

    // Initially should have 10 attempts for medium tier
    $remaining = $rateLimiter->remaining($key, 'medium');
    expect($remaining)->toBe(10);

    // After 3 attempts, should have 7 remaining
    for ($i = 0; $i < 3; $i++) {
        $rateLimiter->attempt($key, 'medium');
    }

    $remaining = $rateLimiter->remaining($key, 'medium');
    expect($remaining)->toBe(7);

    RateLimiter::clear($key);
});

test('availableIn method returns seconds until reset', function () {
    $rateLimiter = new BotRateLimiter;
    $key = 'available_in_test_'.uniqid();

    // Exhaust the limit
    for ($i = 0; $i < 10; $i++) {
        $rateLimiter->attempt($key, 'medium');
    }

    $availableIn = $rateLimiter->availableIn($key);

    // Should be less than or equal to 60 seconds (decay time)
    expect($availableIn)->toBeLessThanOrEqual(60)
        ->and($availableIn)->toBeGreaterThan(0);

    RateLimiter::clear($key);
});

test('clear method clears rate limit for a key', function () {
    $rateLimiter = new BotRateLimiter;
    $key = 'clear_test_'.uniqid();

    // Exhaust the limit
    for ($i = 0; $i < 10; $i++) {
        $rateLimiter->attempt($key, 'medium');
    }

    // Should be blocked now
    expect($rateLimiter->attempt($key, 'medium'))->toBeFalse();

    // Clear and try again
    $rateLimiter->clear($key);

    // Should be allowed again
    expect($rateLimiter->attempt($key, 'medium'))->toBeTrue();

    RateLimiter::clear($key);
});

test('attempt defaults to medium tier when invalid tier provided', function () {
    $rateLimiter = new BotRateLimiter;
    $key = 'invalid_tier_'.uniqid();

    // Use invalid tier - should default to medium (10 req/min)
    for ($i = 0; $i < 10; $i++) {
        expect($rateLimiter->attempt($key, 'invalid_tier'))->toBeTrue();
    }

    // 11th should fail (medium tier limit)
    expect($rateLimiter->attempt($key, 'invalid_tier'))->toBeFalse();

    RateLimiter::clear($key);
});
