<?php

namespace Database\Factories;

use App\Models\AbandonedObject;
use Carbon\Carbon;

class AbandonedObjectFactory
{
    public static function fromApi(array $data, $type): AbandonedObject
    {
        return AbandonedObject::updateOrCreate(
            [
                'eri_id' => $data['id'],
                'type' => $type,
            ],
            [
                'address' => $data['position'],
                'date_abounded' => self::formatDate($data['abandonedObjectStateDate'] ?? null),
                'date_revision' => self::formatDate($data['inspectionDate'] ?? null),
            ]
        );
    }

    protected static function formatDate($timestamp): ?string
    {
        if (!$timestamp) {
            return null;
        }

        try {
            return Carbon::createFromTimestampMs($timestamp)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
