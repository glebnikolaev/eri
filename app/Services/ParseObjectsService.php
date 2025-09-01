<?php

namespace App\Services;

use App\Enums\AbandonedObjectTypeEnum;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ParseObjectsService
{
    public function fetch(array $payload = [], int $type = AbandonedObjectTypeEnum::HOUSE->value): ?array
    {
        $payload = array_merge($this->getPayload($type), $payload);
        $uri = $this->getUri($type);
        //$uri = 'https://eri2.nca.by/api/guest/abandonedObject/search';
        $client = new Client([
            'base_uri' => 'https://eri2.nca.by',
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:141.0) Gecko/20100101 Firefox/141.0',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'ru,en-US;q=0.7,en;q=0.3',
                'Accept-Encoding' => 'gzip, deflate, br, zstd',
                'Content-Type' => 'application/json',
                'Origin' => 'https://eri2.nca.by',
                'Referer' => 'https://eri2.nca.by/guest/abandonedObject',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'no-cache',
            ],
        ]);

        try {
            $response = $client->post($uri, [
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            Log::info('Данные успешно получены.', ['count' => count($data['data']['content'] ?? [])]);

            return $data['data']['content'] ?? null;
        } catch (\Throwable $e) {
            Log::error('Ошибка при запросе: ' . $e->getMessage());
            return [];
        }
    }

    private function getPayload($type) :array
    {
        if ($type == AbandonedObjectTypeEnum::HOUSE->value) {
            return [
                "pageSize" => 30,
                "pageNumber" => 0,
                "sortBy" => 1,
                "sortDesc" => true,
                "abandonedObjectId" => null,
                "fromInspectionDate" => null,
                "toInspectionDate" => null,
                "fromEventDate" => null,
                "toEventDate" => null,
                "abandonedObjectTypeId" => 1,
                "stateTypeId" => 15,
                "stateGroupId" => 2,
                "stateSearchCategoryId" => 1,
                "streetId" => null,
                "ateId" => null,
                "oneBasePrice" => false,
                "emergency" => false,
                "destroyed" => false,
                "fromDeterioration" => null,
                "toDeterioration" => null,
                "fromMoneyAmount" => null,
                "toMoneyAmount" => null
            ];
        } elseif ($type == AbandonedObjectTypeEnum::LAND_PLOT->value) {
            return [
                "pageSize" =>  30,
                "pageNumber" =>  0,
                "sortBy" =>  1,
                "sortDesc" =>  true,
                "id" =>  null,
                "ateId" =>  null,
                "streetId" =>  null,
                "rightTypeId" =>  null,
                "fromStartDate" =>  null,
                "toStartDate" =>  null,
                "waterDistanceFrom" =>  null,
                "waterDistanceTo" =>  null,
                "sewerageDistanceFrom" =>  null,
                "sewerageDistanceTo" =>  null,
                "gasDistanceFrom" =>  null,
                "gasDistanceTo" =>  null,
                "waterAreaDistanceFrom" =>  null,
                "waterAreaDistanceTo" =>  null,
                "forestDistanceFrom" =>  null,
                "forestDistanceTo" =>  null,
                "electricityDistanceFrom" =>  null,
                "electricityDistanceTo" =>  null,
                "railwayDistanceFrom" =>  null,
                "railwayDistanceTo" =>  null,
                "modernRoadDistanceFrom" =>  null,
                "modernRoadDistanceTo" =>  null,
                "investmentRegistryTypeId" =>  1,
                "investmentObjectAuctionTypeId" =>  1,
                "waterDistanceStateId" =>  0,
                "sewerageDistanceStateId" =>  0,
                "gasDistanceStateId" =>  0,
                "forestDistanceStateId" =>  0,
                "waterAreaDistanceStateId" =>  0,
                "electricityDistanceStateId" =>  0,
                "railwayDistanceStateId" =>  0,
                "modernRoadDistanceStateId" =>  0,
                "squareFrom" =>  "0.1",
                "squareTo" =>  null,
                "purposeIds" =>  [
                            10904,
                            10902
                        ],
                "railroadStationDistanceFrom" =>  null,
                "railroadStationDistanceTo" =>  null,
                "railroadStationDistanceStateId" =>  0,
            ];
        }
    }
    private function getUri(string $type) : string
    {
        switch ($type) {
            case AbandonedObjectTypeEnum::HOUSE->value:
                return 'https://eri2.nca.by/api/guest/abandonedObject/search';
            case AbandonedObjectTypeEnum::LAND_PLOT->value:
                return 'https://eri2.nca.by/api/guest/investmentObject/search';
        }
    }
}
