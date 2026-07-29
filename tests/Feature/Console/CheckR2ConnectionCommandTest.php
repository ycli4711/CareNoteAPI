<?php

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

test('the R2 disk uses the S3-compatible driver defaults', function () {
    expect(config('filesystems.disks.r2'))
        ->driver->toBe('s3')
        ->region->toBe('auto')
        ->use_path_style_endpoint->toBeFalse()
        ->throw->toBeTrue();
});

test('the R2 connection check fails before network access when configuration is incomplete', function () {
    config()->set('filesystems.disks.r2.key');
    config()->set('filesystems.disks.r2.secret');
    config()->set('filesystems.disks.r2.bucket');
    config()->set('filesystems.disks.r2.endpoint');

    $this->artisan('r2:check')
        ->expectsOutputToContain('Missing R2 configuration')
        ->assertFailed();
});

test('the R2 connection check verifies and removes its probe object', function () {
    config()->set('filesystems.disks.r2', [
        ...config('filesystems.disks.r2'),
        'key' => 'test-key',
        'secret' => 'test-secret',
        'bucket' => 'test-bucket',
        'endpoint' => 'https://example.r2.cloudflarestorage.com',
    ]);

    $payload = null;
    $disk = Mockery::mock(FilesystemAdapter::class);

    $disk->shouldReceive('put')
        ->once()
        ->withArgs(function (string $path, string $contents) use (&$payload): bool {
            $payload = $contents;

            return str_starts_with($path, '_healthchecks/');
        });
    $disk->shouldReceive('exists')->twice()->andReturn(true, false);
    $disk->shouldReceive('get')->once()->andReturnUsing(function () use (&$payload): ?string {
        return $payload;
    });
    $disk->shouldReceive('temporaryUrl')
        ->once()
        ->andReturn('https://example.r2.cloudflarestorage.com/test-bucket/probe.txt?signature=test');
    $disk->shouldReceive('delete')->once();

    Storage::shouldReceive('disk')->once()->with('r2')->andReturn($disk);

    $status = Artisan::call('r2:check');
    $output = Artisan::output();

    expect($output)
        ->toContain('R2 connection check passed.')
        ->toContain('Bucket: test-bucket')
        ->and($status)->toBe(0);
});
