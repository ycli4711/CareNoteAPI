<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CheckR2Connection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'r2:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify the configured Cloudflare R2 connection with a temporary probe object';

    public function handle(): int
    {
        $config = config('filesystems.disks.r2', []);
        $required = [
            'key' => 'R2_ACCESS_KEY_ID',
            'secret' => 'R2_SECRET_ACCESS_KEY',
            'bucket' => 'R2_BUCKET',
            'endpoint' => 'R2_ENDPOINT',
        ];

        $missing = collect($required)
            ->filter(fn (string $environmentVariable, string $key): bool => blank($config[$key] ?? null))
            ->values()
            ->all();

        if ($missing !== []) {
            $this->components->error('Missing R2 configuration: '.implode(', ', $missing));

            return self::FAILURE;
        }

        $disk = Storage::disk('r2');
        $path = '_healthchecks/'.Str::uuid().'.txt';
        $payload = implode('|', [
            'CareNote R2 connectivity check',
            now()->toIso8601String(),
            Str::random(32),
        ]);
        $created = false;

        try {
            $disk->put($path, $payload);
            $created = true;

            if (! $disk->exists($path)) {
                throw new RuntimeException('The probe object was not found after upload.');
            }

            if (! hash_equals($payload, $disk->get($path))) {
                throw new RuntimeException('The probe object content did not match after download.');
            }

            $temporaryUrl = $disk->temporaryUrl($path, now()->addMinutes(5));

            if (filter_var($temporaryUrl, FILTER_VALIDATE_URL) === false) {
                throw new RuntimeException('R2 did not return a valid temporary URL.');
            }

            $disk->delete($path);
            $created = false;

            if ($disk->exists($path)) {
                throw new RuntimeException('The probe object still exists after deletion.');
            }
        } catch (Throwable $exception) {
            if ($created) {
                try {
                    $disk->delete($path);
                } catch (Throwable) {
                    // Keep the original connection failure as the primary error.
                }
            }

            $this->components->error('R2 connection check failed: '.$this->safeErrorMessage($exception, $config));

            return self::FAILURE;
        }

        $endpointHost = parse_url((string) $config['endpoint'], PHP_URL_HOST);

        $this->components->info('R2 connection check passed.');
        $this->line('Bucket: '.$config['bucket']);
        $this->line('Endpoint: '.($endpointHost ?: 'configured'));

        return self::SUCCESS;
    }

    /**
     * Redact configured credentials before displaying a provider error.
     *
     * @param  array<string, mixed>  $config
     */
    private function safeErrorMessage(Throwable $exception, array $config): string
    {
        $message = $exception->getMessage();

        foreach (['key', 'secret'] as $key) {
            $credential = $config[$key] ?? null;

            if (is_string($credential) && $credential !== '') {
                $message = str_replace($credential, '[redacted]', $message);
            }
        }

        return Str::limit($message, 500);
    }
}
