<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateJwtKeysCommand extends Command
{
    protected $signature = 'jwt:generate-keys {--force : Overwrite existing keys}';

    protected $description = 'Generate RS256 private/public key pair for JWT signing';

    public function handle(): int
    {
        $privateKeyPath = config('jwt.keys.private');
        $publicKeyPath  = config('jwt.keys.public');

        if (file_exists($privateKeyPath) && ! $this->option('force')) {
            $this->warn('JWT keys already exist. Use --force to overwrite.');

            return self::SUCCESS;
        }

        $dir = dirname($privateKeyPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $resource = openssl_pkey_new([
            'digest_alg'       => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if (! $resource) {
            $this->error('Failed to generate RSA key pair. Is the OpenSSL extension enabled?');

            return self::FAILURE;
        }

        openssl_pkey_export($resource, $privateKey);
        $publicKey = openssl_pkey_get_details($resource)['key'];

        file_put_contents($privateKeyPath, $privateKey);
        chmod($privateKeyPath, 0600);

        file_put_contents($publicKeyPath, $publicKey);
        chmod($publicKeyPath, 0644);

        $this->info('JWT RS256 key pair generated:');
        $this->line("  Private: {$privateKeyPath}");
        $this->line("  Public:  {$publicKeyPath}");

        return self::SUCCESS;
    }
}
