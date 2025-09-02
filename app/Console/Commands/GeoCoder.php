<?php

namespace App\Console\Commands;

use App\Enums\ObjectStateEnum;
use App\Enums\AbandonedObjectTypeEnum;
use App\Models\AbandonedObject;
use App\Services\GeocoderService;
use App\Services\ParseObjectsService;
use Database\Factories\AbandonedObjectFactory;
use Illuminate\Console\Command;

class GeoCoder extends Command
{
    protected GeocoderService $service;

    public function __construct(GeocoderService $geocoder)
    {
        parent::__construct();
        $this->service = $geocoder;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновление координат';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        AbandonedObject::where('type', AbandonedObjectTypeEnum::HOUSE->value)
            ->whereNull('coords')
            ->whereNotNull('address')
            ->chunk(200, function ($objects) {
                foreach ($objects as $object) {
                    $coords = $this->service->geocode($object->address);

                    if ($coords) {
                        $object->update(['coords' => $coords]);
                    }
                }
            });
    }
}
