<?php

use App\Logging\ModuleChannelRegistrar;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Facades\Module;

beforeEach(function () {
    // Clear any prior auto-registration so each test starts from a known state.
    foreach (Module::all() as $module) {
        Config::offsetUnset('logging.channels.'.strtolower($module->getName()));
    }
});

it('registers a daily channel for every nwidart module slug', function () {
    ModuleChannelRegistrar::register();

    foreach (Module::all() as $module) {
        $slug = strtolower($module->getName());
        $channel = config("logging.channels.{$slug}");

        expect($channel)->not->toBeNull();
        expect($channel['driver'])->toBe('daily');
        expect($channel['path'])->toBe(storage_path("logs/{$slug}/{$slug}.log"));
        expect($channel['days'])->toBe((int) config('logging.module_channels.retention_days', 90));
    }
});

it('honors LOG_MODULE_RETENTION_DAYS via the module_channels config', function () {
    Config::set('logging.module_channels.retention_days', 7);
    ModuleChannelRegistrar::register();

    $first = strtolower(array_values(Module::all())[0]->getName());
    expect(config("logging.channels.{$first}.days"))->toBe(7);
});

it('does not override a channel that was already configured', function () {
    $first = strtolower(array_values(Module::all())[0]->getName());

    Config::set("logging.channels.{$first}", [
        'driver' => 'single',
        'path' => storage_path('logs/pinned.log'),
    ]);

    ModuleChannelRegistrar::register();

    expect(config("logging.channels.{$first}.driver"))->toBe('single');
    expect(config("logging.channels.{$first}.path"))->toBe(storage_path('logs/pinned.log'));
});

it('lets Log::channel() resolve the auto-registered module channel', function () {
    ModuleChannelRegistrar::register();

    $first = strtolower(array_values(Module::all())[0]->getName());

    // Should not throw — channel must be a fully-formed daily channel by now.
    $logger = Log::channel($first);

    expect($logger)->not->toBeNull();
});

it('writes module log lines under the per-module path on the configured channel', function () {
    ModuleChannelRegistrar::register();

    $slug = 'clientspecification';
    if (! array_key_exists($slug, Module::all())) {
        $this->markTestSkipped('ClientSpecification module not present in this install — channel rotation test requires it.');
    }

    $logFile = storage_path("logs/{$slug}/{$slug}-".now()->format('Y-m-d').'.log');
    if (file_exists($logFile)) {
        @unlink($logFile);
    }

    Log::channel($slug)->info('autoregistration smoke test', ['marker' => 'modchan-smoke']);

    expect(file_exists($logFile))->toBeTrue();
    expect(file_get_contents($logFile))->toContain('modchan-smoke');

    @unlink($logFile);
    @rmdir(dirname($logFile));
});
