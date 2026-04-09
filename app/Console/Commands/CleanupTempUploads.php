<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupTempUploads extends Command
{
    protected $signature   = 'app:cleanup-temp-uploads {--hours=24 : Delete files older than this many hours}';
    protected $description = 'Remove orphaned temporary Livewire upload files older than the given threshold';

    public function handle(): int
    {
        $hours     = (int) $this->option('hours');
        $threshold = now()->subHours($hours)->timestamp;
        $disk      = Storage::disk('local');

        $files   = $disk->files('livewire-tmp');
        $deleted = 0;

        foreach ($files as $file) {
            if ($disk->lastModified($file) < $threshold) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->info("Deleted {$deleted} temp file(s) older than {$hours}h.");

        return self::SUCCESS;
    }
}
