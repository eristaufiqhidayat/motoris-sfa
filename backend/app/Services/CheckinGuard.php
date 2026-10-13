<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\Visit;

/**
 * Pemeriksaan check-in: menolak yang jelas curang, menandai yang janggal.
 *
 * Ditolak : lokasi palsu (mock location), di luar radius, GPS tidak dikirim.
 * Ditandai: akurasi GPS buruk, pindah antar-outlet lebih cepat dari kendaraan wajar,
 *           koordinat persis sama dengan check-in sebelumnya, outlet belum punya titik.
 */
class CheckinGuard
{
    public const RADIUS_METER = 150;

    public const AKURASI_MAKS_METER = 100;

    public const KECEPATAN_MAKS_KMJ = 80;

    public const FLAGS = [
        'akurasi_buruk' => 'Akurasi GPS buruk',
        'terlalu_cepat' => 'Pindah outlet terlalu cepat',
        'koordinat_sama' => 'Koordinat sama persis dengan check-in sebelumnya',
        'outlet_tanpa_titik' => 'Outlet belum punya titik lokasi',
    ];

    /** @return array{jarak: ?int, flags: list<string>, tolak: ?string} */
    public function periksa(int $userId, Outlet $outlet, ?float $lat, ?float $lng, ?float $akurasi, bool $mock): array
    {
        if ($mock) {
            return $this->tolak('Lokasi palsu terdeteksi. Matikan aplikasi Fake GPS / mock location, lalu coba lagi.');
        }

        $flags = [];
        if (! $outlet->lat || ! $outlet->lng) {
            $flags[] = 'outlet_tanpa_titik';
        } elseif ($lat === null || $lng === null) {
            return $this->tolak('Lokasi HP tidak terbaca. Aktifkan GPS dan izinkan akses lokasi.');
        }

        $jarak = null;
        if ($lat !== null && $lng !== null && $outlet->lat && $outlet->lng) {
            $jarak = (int) round(self::haversine($lat, $lng, $outlet->lat, $outlet->lng));
            if ($jarak > self::RADIUS_METER) {
                return $this->tolak("Posisi Anda {$jarak} m dari outlet. Check-in maksimal ".self::RADIUS_METER.' m.');
            }
        }

        if ($akurasi !== null && $akurasi > self::AKURASI_MAKS_METER) {
            $flags[] = 'akurasi_buruk';
        }

        $sebelum = Visit::where('user_id', $userId)->whereDate('tanggal', today())
            ->whereNotNull('lat')->latest('checkin_at')->first();
        if ($sebelum && $lat !== null && $lng !== null && $sebelum->outlet_id !== $outlet->id) {
            if ((float) $sebelum->lat === $lat && (float) $sebelum->lng === $lng) {
                $flags[] = 'koordinat_sama';
            }
            $meter = self::haversine($sebelum->lat, $sebelum->lng, $lat, $lng);
            $detik = max(1, now()->diffInSeconds($sebelum->checkin_at, true));
            if ($meter > 300 && ($meter / 1000) / ($detik / 3600) > self::KECEPATAN_MAKS_KMJ) {
                $flags[] = 'terlalu_cepat';
            }
        }

        return ['jarak' => $jarak, 'flags' => $flags, 'tolak' => null];
    }

    private function tolak(string $pesan): array
    {
        return ['jarak' => null, 'flags' => [], 'tolak' => $pesan];
    }

    public static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
