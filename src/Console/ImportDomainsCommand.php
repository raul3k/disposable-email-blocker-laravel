<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Raul3k\BlockDisposable\Core\Sources\SourceRegistry;
use Throwable;

class ImportDomainsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disposable:import
                            {source : The source to import from}
                            {--chunk=1000 : Number of domains to insert per batch}
                            {--clear : Clear existing domains from this source before importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import disposable domains from a specific source';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string $sourceName */
        $sourceName = $this->argument('source');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $clear = (bool) $this->option('clear');

        /** @var string $table */
        $table = config('disposable-blocker.database.table', 'disposable_domains');
        /** @var string|null $connection */
        $connection = config('disposable-blocker.database.connection');

        $registry = new SourceRegistry();

        if (!$registry->has($sourceName)) {
            $this->error("Source '{$sourceName}' not found.");
            $this->newLine();
            $this->info('Available sources:');
            foreach ($registry->list() as $name) {
                $this->line("  - {$name}");
            }

            return self::FAILURE;
        }

        $source = $registry->get($sourceName);

        $this->info("Importing from <comment>{$sourceName}</comment>...");

        if ($clear) {
            $deleted = DB::connection($connection)
                ->table($table)
                ->where('source', $sourceName)
                ->delete();

            $this->line("  Cleared <info>{$deleted}</info> existing domains from this source.");
        }

        try {
            $domains = [...$source->fetch()];
            $count = count($domains);

            if ($count === 0) {
                $this->warn('No domains found in source.');

                return self::SUCCESS;
            }

            $this->line("  Found <info>{$count}</info> domains.");

            $bar = $this->output->createProgressBar(count($domains));
            $bar->start();

            $inserted = 0;
            foreach (array_chunk($domains, $chunkSize) as $chunk) {
                $data = array_map(function (string $domain) use ($sourceName): array {
                    return [
                        'domain' => strtolower(trim($domain)),
                        'source' => $sourceName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $chunk);

                DB::connection($connection)
                    ->table($table)
                    ->upsert($data, ['domain'], ['source', 'updated_at']);

                $inserted += count($chunk);
                $bar->advance(count($chunk));
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("Successfully imported {$inserted} domains from {$sourceName}.");

            $totalInDb = DB::connection($connection)->table($table)->count();
            $this->info("Total unique domains in database: {$totalInDb}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Failed to import: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
