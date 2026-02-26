<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Raul3k\DisposableBlocker\Core\Sources\SourceRegistry;
use Throwable;

class UpdateDomainsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disposable:update
                            {--source= : Specific source to update from (default: all)}
                            {--chunk=1000 : Number of domains to insert per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update disposable domains from configured sources';

    public function __construct(
        private readonly SourceRegistry $registry
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string $table */
        $table = config('disposable-blocker.database.table', 'disposable_domains');
        /** @var string|null $connection */
        $connection = config('disposable-blocker.database.connection');

        /** @var string|null $sourceOption */
        $sourceOption = $this->option('source');
        $chunkSize = max(1, (int) $this->option('chunk'));

        if ($sourceOption !== null) {
            $sources = [$sourceOption];
        } else {
            $sources = $this->registry->list();
        }

        $this->info('Updating disposable domains...');
        $this->newLine();

        $totalImported = 0;

        foreach ($sources as $name) {
            if (!$this->registry->has($name)) {
                $this->error("Source '{$name}' not found.");
                continue;
            }

            $source = $this->registry->get($name);
            $this->line("  Fetching from <info>{$name}</info>...");

            try {
                $inserted = 0;
                $chunk = [];

                foreach ($source->fetch() as $domain) {
                    $chunk[] = [
                        'domain' => strtolower(trim($domain)),
                        'source' => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($chunk) >= $chunkSize) {
                        DB::connection($connection)
                            ->table($table)
                            ->upsert($chunk, ['domain'], ['source', 'updated_at']);

                        $inserted += count($chunk);
                        $chunk = [];
                    }
                }

                if ($chunk !== []) {
                    DB::connection($connection)
                        ->table($table)
                        ->upsert($chunk, ['domain'], ['source', 'updated_at']);

                    $inserted += count($chunk);
                }

                if ($inserted === 0) {
                    $this->warn("    No domains found.");
                    continue;
                }

                $this->line("    Imported <info>{$inserted}</info> domains.");
                $totalImported += $inserted;
            } catch (Throwable $e) {
                $this->error("    Failed: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Done! Total domains processed: {$totalImported}");

        $totalInDb = DB::connection($connection)->table($table)->count();
        $this->info("Total unique domains in database: {$totalInDb}");

        return self::SUCCESS;
    }
}
