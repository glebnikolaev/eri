<?php

namespace App\Console\Commands;

use App\Enums\AbandonedObjectTypeEnum;
use Illuminate\Console\Command;
use App\Services\InvestmentBordersSyncService;

class SyncInvestmentBorders extends Command
{
    protected $signature = 'borders:sync
                            {--all : Обновлять все (включая те, где borders уже заполнено)}
                            {--limit= : Лимит записей за запуск}';

    protected $description;

    public function __construct()
    {
        parent::__construct();
        $this->description = 'Синхронизация borders для инвест-объектов (type=' . AbandonedObjectTypeEnum::LAND_PLOT->value . ') из ERI2 API';
    }

    public function handle(InvestmentBordersSyncService $service): int
    {
        $onlyMissing = !$this->option('all');
        $limit = $this->option('limit') ? (int)$this->option('limit') : null;

        $this->info(sprintf(
            'Старт: type=%d, onlyMissing=%s, limit=%s',
            AbandonedObjectTypeEnum::LAND_PLOT->value,
            $onlyMissing ? 'true' : 'false',
            $limit ?? '—'
        ));

        $result = $service->sync($onlyMissing, $limit);

        $this->newLine();
        $this->table(
            ['processed', 'updated', 'skipped', 'failed'],
            [[ $result['processed'], $result['updated'], $result['skipped'], $result['failed'] ]]
        );

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
