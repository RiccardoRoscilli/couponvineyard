<?php

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Command;

class SlopeInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slope:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display Slope API configuration and status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Slope API Configuration ===');
        $this->newLine();

        // Environment
        $env = config('app.env');
        $this->line("Environment: <fg=yellow>{$env}</>");

        // Base URL
        $baseUrl = config('services.slope.base_url');
        $isStaging = str_contains($baseUrl, 'staging');
        $urlColor = $isStaging ? 'yellow' : 'green';
        $urlLabel = $isStaging ? 'STAGING' : 'PRODUCTION';
        $this->line("Base URL: <fg={$urlColor}>{$baseUrl}</> [{$urlLabel}]");

        // Other settings
        $this->line("Timeout: " . config('services.slope.timeout') . "s");
        $this->line("Retry Attempts: " . config('services.slope.retry_attempts'));
        $this->line("Sync Days Ahead: " . config('services.slope.sync_days_ahead'));
        $this->line("Sync Enabled: " . (config('services.slope.enabled') ? 'Yes' : 'No'));

        $this->newLine();
        $this->info('=== Enabled Locations ===');
        $this->newLine();

        $locations = Location::where('slope_enabled', true)->get();

        if ($locations->isEmpty()) {
            $this->warn('No locations with Slope enabled.');
        } else {
            $this->table(
                ['ID', 'Name', 'Has Token', 'Token Preview'],
                $locations->map(function ($location) {
                    $hasToken = !empty($location->slope_bearer_token);
                    $tokenPreview = $hasToken 
                        ? substr($location->slope_bearer_token, 0, 8) . '...' 
                        : 'N/A';
                    
                    return [
                        $location->id,
                        $location->name,
                        $hasToken ? '✓' : '✗',
                        $tokenPreview,
                    ];
                })
            );
        }

        $this->newLine();
        $this->info('=== Quick Commands ===');
        $this->line('Test connection:  php artisan slope:sync --test-connection');
        $this->line('Dry run sync:     php artisan slope:sync --dry-run');
        $this->line('Sync all:         php artisan slope:sync');
        
        if ($locations->isNotEmpty()) {
            $firstId = $locations->first()->id;
            $this->line("Sync location:    php artisan slope:sync --location={$firstId}");
        }

        $this->newLine();
        
        if ($isStaging) {
            $this->comment('💡 You are using STAGING environment');
            $this->comment('   To switch to production, set in .env:');
            $this->comment('   APP_ENV=production');
            $this->comment('   or');
            $this->comment('   SLOPE_BASE_URL=https://api.slope.it');
        } else {
            $this->comment('⚠️  You are using PRODUCTION environment');
            $this->comment('   To switch to staging, set in .env:');
            $this->comment('   APP_ENV=local');
            $this->comment('   or');
            $this->comment('   SLOPE_BASE_URL=https://api.staging.slope.it');
        }

        return self::SUCCESS;
    }
}
