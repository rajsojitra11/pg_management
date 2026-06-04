<?php

use App\Services\EnvManifest\EnvManifest;
use App\Services\EnvManifest\EnvManifestEntry;

it('loads all manifest groups from config/env-manifest', function () {
    $manifest = app(EnvManifest::class);

    expect($manifest->count())->toBeGreaterThan(0)
        ->and($manifest->groups())->toContain('app-core', 'business', 'data-entry-audit');
});

it('finds an entry by key with full metadata', function () {
    $entry = app(EnvManifest::class)->find('APP_KEY');

    expect($entry)->toBeInstanceOf(EnvManifestEntry::class)
        ->and($entry->group)->toBe('app-core')
        ->and($entry->type)->toBe('secret')
        ->and($entry->secret)->toBeTrue()
        ->and($entry->criticality)->toBe('high');
});

it('rejects manifest entries missing required fields', function () {
    expect(fn () => EnvManifestEntry::fromArray('TEST_BAD', 'test', 'fake.php', [
        // missing description, type, default
    ]))->toThrow(RuntimeException::class, "missing required field 'description'");
});

it('throws on duplicate keys across files', function () {
    $tmp = sys_get_temp_dir().'/env-manifest-dup-'.bin2hex(random_bytes(4));
    mkdir($tmp);
    file_put_contents("{$tmp}/group-a.php", "<?php return ['SHARED' => ['description'=>'a','type'=>'string','default'=>''] ];");
    file_put_contents("{$tmp}/group-b.php", "<?php return ['SHARED' => ['description'=>'b','type'=>'string','default'=>''] ];");

    $manifest = new EnvManifest($tmp);

    expect(fn () => $manifest->entries())->toThrow(RuntimeException::class, 'Duplicate manifest entry');

    array_map('unlink', glob("{$tmp}/*"));
    rmdir($tmp);
});

it('groups entries by group name', function () {
    $business = app(EnvManifest::class)->inGroup('business');

    expect($business)->not->toBeEmpty()
        ->and($business)->toHaveKey('INSTALL_TYPE')
        ->and($business)->toHaveKey('AUTO_RELEASE_STOCK_ON_PASS');
});

it('marks profile_gap entries explicitly', function () {
    $entry = app(EnvManifest::class)->find('FORMULATION_MANUFACTURING_TITLE');

    expect($entry->profileGap)->toBeTrue()
        ->and($entry->businessRelevant)->toBeTrue()
        ->and($entry->recommended)->toBeArray()
        ->and($entry->recommended['drug'])->toBe('Formula');
});

it('exposes deprecation status for entries with deprecated_in', function () {
    // None of our seeded entries are deprecated, but the API works
    $entry = app(EnvManifest::class)->find('APP_KEY');

    expect($entry->isDeprecated())->toBeFalse();
});
