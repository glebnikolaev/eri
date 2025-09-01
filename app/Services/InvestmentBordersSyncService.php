<?php

namespace App\Services;

use App\Models\AbandonedObject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InvestmentBordersSyncService
{
    /**
     * Пробегает по объектам (type=2), тянет borders и сохраняет.
     * @param bool $onlyMissing  true — обновлять только у тех, где borders = null
     * @param int|null $limit    лимит обработанных записей за запуск (null = без лимита)
     * @return array{processed:int, updated:int, skipped:int, failed:int}
     */
    public function sync(bool $onlyMissing = true, ?int $limit = null): array
    {
        $query = AbandonedObject::query()
            ->where('type', 2)
            ->when($onlyMissing, fn ($q) => $q->whereNull('borders'));

        $processed = $updated = $skipped = $failed = 0;

        $query->orderBy('id')->chunkById(100, function ($chunk) use (&$processed, &$updated, &$skipped, &$failed, $limit) {
            foreach ($chunk as $obj) {
                if ($limit !== null && $processed >= $limit) {
                    return false; // прерываем дальнейшие чанки
                }

                $processed++;

                $eriId = (string)$obj->eri_id;
                if ($eriId === '') {
                    $skipped++;
                    continue;
                }

                try {
                    $details = $this->fetchDetails($eriId); // data.* из ERI
                    $borders = $details['borders'] ?? null;

                    if (!$borders || !is_array($borders) || empty($borders['coordinates'])) {
                        $skipped++;
                        continue;
                    }

                    // Сохраняем как есть (API отдаёт координаты в порядке [lat, lon], что удобно для Yandex Maps)
                    $obj->update(['borders' => $borders]);
                    $updated++;

                    // (опционально) не лупить API слишком быстро
                    usleep(150 * 1000);
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('Borders sync failed', [
                        'eri_id' => $obj->eri_id,
                        'error'  => $e->getMessage(),
                    ]);
                }
            }
        });

        return compact('processed', 'updated', 'skipped', 'failed');
    }

    /**
     * POST https://eri2.nca.by/api/guest/investmentObject/{eri_id}/forView
     * Возвращает массив data из ответа.
     */
    protected function fetchDetails(string $eriId): array
    {
        $baseUrl = 'https://eri2.nca.by';
        $timeout = (int) config('services.eri2.timeout', 15);

        $resp = Http::baseUrl($baseUrl)
            ->timeout($timeout)
            ->retry(3, 250) // 3 попытки с бэкофом 250мс
            ->withHeaders([
                'Accept' => 'application/json, text/plain, */*',
                'Content-Type' => 'application/json',
            ])
            ->post("/api/guest/investmentObject/{$eriId}/forView", []); // пустое JSON-тело

        if (!$resp->successful()) {
            throw new \RuntimeException("ERI2 http {$resp->status()}");
        }

        $json = $resp->json();
        return is_array($json) ? ($json['data'] ?? []) : [];
    }
}
