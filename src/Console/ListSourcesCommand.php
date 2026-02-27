<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Console;

use Illuminate\Console\Command;
use Raul3k\DisposableBlocker\Core\Sources\SourceRegistry;

class ListSourcesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disposable:list-sources';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List available disposable domain sources';

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
        $sources = $this->registry->all();

        $this->info('Available disposable domain sources:');
        $this->newLine();

        $tableData = [];

        foreach ($sources as $name => $source) {
            $url = $source->getUrl();
            $tableData[] = [
                'name' => $name,
                'url' => $url !== null && strlen($url) > 60
                    ? substr($url, 0, 57) . '...'
                    : ($url ?? 'N/A'),
            ];
        }

        $this->table(
            ['Name', 'URL'],
            $tableData
        );

        $this->newLine();
        $this->line('Use <info>php artisan disposable:import {source}</info> to import from a specific source.');
        $this->line('Use <info>php artisan disposable:update</info> to update from all sources.');

        return self::SUCCESS;
    }
}
