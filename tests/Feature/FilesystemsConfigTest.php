<?php

/**
 * config/filesystems.php picks the 'private' disk's driver based on the
 * PRIVATE_FILESYSTEM_DRIVER env var (added so this app can run on a
 * deploy target with no durable local disk, e.g. Vercel - see
 * DEPLOY-VERCEL.md). Since that choice is baked into the config array the
 * moment the file is evaluated, this loads the file directly with a
 * controlled env rather than going through Laravel's already-booted (and
 * therefore already-decided) config() repository.
 */
function withPrivateFilesystemDriver(?string $driver, callable $callback): mixed
{
    $set = function (?string $value) {
        if ($value === null) {
            putenv('PRIVATE_FILESYSTEM_DRIVER');
            unset($_ENV['PRIVATE_FILESYSTEM_DRIVER'], $_SERVER['PRIVATE_FILESYSTEM_DRIVER']);
        } else {
            putenv("PRIVATE_FILESYSTEM_DRIVER={$value}");
            $_ENV['PRIVATE_FILESYSTEM_DRIVER'] = $value;
            $_SERVER['PRIVATE_FILESYSTEM_DRIVER'] = $value;
        }
    };

    $set($driver);

    try {
        return $callback(require base_path('config/filesystems.php'));
    } finally {
        $set(null);
    }
}

test('the private disk defaults to the local driver', function () {
    withPrivateFilesystemDriver(null, function (array $config) {
        expect($config['disks']['private']['driver'])->toBe('local')
            ->and($config['disks']['private']['root'])->toBe(storage_path('app/private'))
            ->and($config['disks']['private']['visibility'])->toBe('private');
    });
});

test('setting PRIVATE_FILESYSTEM_DRIVER=s3 switches the private disk to the s3 driver', function () {
    withPrivateFilesystemDriver('s3', function (array $config) {
        $disk = $config['disks']['private'];

        expect($disk['driver'])->toBe('s3')
            ->and($disk)->not->toHaveKey('root')
            ->and($disk['visibility'])->toBe('private')
            ->and($disk)->toHaveKeys(['key', 'secret', 'region', 'bucket', 'endpoint', 'use_path_style_endpoint']);
    });
});
