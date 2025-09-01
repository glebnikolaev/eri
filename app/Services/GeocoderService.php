<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeocoderService
{
    public function geocode(string $address): ?string
    {
        $key = config('services.yandex_geocoder.key');
        $response = Http::get("https://geocode-maps.yandex.ru/v1/", [
            'apikey' => $key,
            'format' => 'json',
            'geocode' => $address,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $pos = data_get($response->json(), 'response.GeoObjectCollection.featureMember.0.GeoObject.Point.pos');

        if (!$pos) return null;

        // Yandex возвращает "долгота широта"
        $coords = explode(' ', $pos);
        return "{$coords[1]},{$coords[0]}";
    }
}
