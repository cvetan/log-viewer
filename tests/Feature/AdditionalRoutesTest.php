<?php

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

test('an additional route can be registered', function () {
    config()->set('log-viewer.routes', [
        'security' => [
            'path' => 'security-logs',
        ],
    ]);

    reloadRoutes();

    expect(route('log-viewer.security.index'))->toContain('security-logs');
});

test('additional route api returns only configured include_files', function () {
    generateLogFiles(['laravel.log', 'security.log'], randomContent: true);

    config()->set('log-viewer.routes', [
        'security' => [
            'path' => 'security-logs',
            'include_files' => ['security*.log'],
        ],
    ]);

    reloadRoutes();

    $mainResponse = getJson(route('log-viewer.files'));
    $mainResponse->assertJsonCount(2);

    $securityResponse = getJson(route('log-viewer.security.files'));
    $securityResponse->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'security.log']);
});

test('additional route does not show files excluded from it', function () {
    generateLogFiles(['laravel.log', 'debug.log', 'security.log'], randomContent: true);

    config()->set('log-viewer.routes', [
        'no-security' => [
            'path' => 'app-logs',
            'exclude_files' => ['security*.log'],
        ],
    ]);

    reloadRoutes();

    $response = getJson(route('log-viewer.no-security.files'));
    $response->assertJsonCount(2);

    $names = collect($response->json())->pluck('name');
    expect($names)->not->toContain('security.log');
});

test('multiple additional routes can be configured independently', function () {
    generateLogFiles(['laravel.log', 'security.log', 'worker.log'], randomContent: true);

    config()->set('log-viewer.routes', [
        'security' => [
            'path' => 'security-logs',
            'include_files' => ['security*.log'],
        ],
        'worker' => [
            'path' => 'worker-logs',
            'include_files' => ['worker*.log'],
        ],
    ]);

    reloadRoutes();

    $securityResponse = getJson(route('log-viewer.security.files'));
    $securityResponse->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'security.log']);

    $workerResponse = getJson(route('log-viewer.worker.files'));
    $workerResponse->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'worker.log']);
});

test('main route is not affected by additional route configuration', function () {
    generateLogFiles(['laravel.log', 'security.log'], randomContent: true);

    config()->set('log-viewer.routes', [
        'security' => [
            'path' => 'security-logs',
            'include_files' => ['security*.log'],
        ],
    ]);

    reloadRoutes();

    $mainResponse = getJson(route('log-viewer.files'));
    $mainResponse->assertJsonCount(2);
});

test('additional route web page is accessible', function () {
    config()->set('log-viewer.routes', [
        'security' => [
            'path' => 'security-logs',
        ],
    ]);

    reloadRoutes();

    get(route('log-viewer.security.index'))->assertStatus(200);
});

test('additional route uses its own path in frontend script variables', function () {
    config()->set('log-viewer.routes', [
        'security' => [
            'path' => 'security-logs',
        ],
    ]);

    reloadRoutes();

    $response = get(route('log-viewer.security.index'));
    $response->assertStatus(200)
        ->assertSee('security-logs');
});
