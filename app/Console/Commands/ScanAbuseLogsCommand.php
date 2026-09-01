<?php

namespace App\Console\Commands;

use App\Services\AbuseDetectorService;
use Illuminate\Console\Command;

class ScanAbuseLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'abuse:scan {--daemon : Run continuously as a background daemon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan Asterisk logs for incoming abused DIDs and record hits in real time';

    /**
     * Execute the console command.
     */
    public function handle(AbuseDetectorService $detector): int
    {
        $isDaemon = $this->option('daemon');

        $this->info("Starting Asterisk Abuse DID log scanner...");

        do {
            $result = $detector->scanAndProcessLogs();
            $newHits = $result['new_hits'] ?? 0;
            $updated = count($result['updated_dids'] ?? []);

            if ($newHits > 0) {
                $this->info("[" . now()->toDateTimeString() . "] Detected {$newHits} new hits across {$updated} DIDs.");
            }

            if ($isDaemon) {
                sleep(2);
            }
        } while ($isDaemon);

        return Command::SUCCESS;
    }
}
