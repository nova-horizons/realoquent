<?php

use NovaHorizons\Realoquent\RealoquentHelpers;

it('can create directories', function () {
    $dir = '/tmp/realoquent-test';
    expect(is_dir($dir))->toBeFalse();
    RealoquentHelpers::validateDirectory($dir);
    expect(is_dir($dir))->toBeTrue();
    rmdir($dir);
    expect(is_dir($dir))->toBeFalse();

});

it('can handle un-writeable existing directories', function () {
    RealoquentHelpers::validateDirectory('/root/realoquent');
})->throws(RuntimeException::class);

it('can handle un-writeable new directories', function () {
    RealoquentHelpers::validateDirectory('/root');
})->throws(RuntimeException::class);
