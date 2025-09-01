<?php

namespace App\Console\Commands;

use App\Enums\ObjectStateEnum;
use App\Enums\AbandonedObjectTypeEnum;
use App\Services\ParseObjectsService;
use Database\Factories\AbandonedObjectFactory;
use Illuminate\Console\Command;

class ParseObjects extends Command
{
    protected ParseObjectsService $service;

    public function __construct(ParseObjectsService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse {--type=2} {--state=15}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Парсинг заброшенных объектов';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $type = (int) $this->option('type');
        $sateId = (int) $this->option('state');

        $this->info("Запрашиваю данные для типа #".AbandonedObjectTypeEnum::from($type)->label()." в статусе #".ObjectStateEnum::from($sateId)->label()."...");
        $result = [];

        for ($page = 0; $page < 100; $page++) {
            $this->info("Запрашиваю страницу: $page");
            $data = $this->service->fetch([
                'stateTypeId' => $sateId,
                'pageNumber' => $page,
            ], $type);

            if (!$data) {
                $this->info("Данные закончились. Завершение...");
                break;
            }

            $this->info("Получено записей:" . count($data));

            $result = array_merge($data, $result);
        }

        $this->info("Получено записей всего:" . count($result));

        foreach ($result as $item) {
            AbandonedObjectFactory::fromApi($item, $type);
        }
    }
}
