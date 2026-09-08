<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use InvalidArgumentException;

/**
 * GeofencingService
 *
 * Validasi geofence server-side untuk Presensi.
 *
 * Acuan:
 * - docs/05_PRESENSI
 * - docs/08_DASHBOARD_SETTINGS_BACKUP
 *
 * Setting canonical:
 * - geofencing_aktif
 * - latitude_sekolah
 * - longitude_sekolah
 * - radius_geofencing
 */
class GeofencingService
{
    private const DEFAULT_RADIUS_METER = 500.0;
    private const EARTH_RADIUS_METER = 6371000.0;

    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Mengambil konfigurasi geofencing canonical dari setting_sistem.
     *
     * @return array{
     *     aktif: bool,
     *     latitude_sekolah: ?float,
     *     longitude_sekolah: ?float,
     *     radius_meter: float
     * }
     */
    public function getConfig(): array
    {
        $keys = [
            'geofencing_aktif',
            'latitude_sekolah',
            'longitude_sekolah',
            'radius_geofencing',
        ];

        $rows = $this->db
            ->table('setting_sistem')
            ->select('setting_key, setting_value')
            ->whereIn('setting_key', $keys)
            ->get()
            ->getResultArray();

        $settings = [];

        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = $row['setting_value'];
        }

        $aktif = $this->toBoolean($settings['geofencing_aktif'] ?? false);
        $latitudeSekolah = $this->toNullableFloat($settings['latitude_sekolah'] ?? null);
        $longitudeSekolah = $this->toNullableFloat($settings['longitude_sekolah'] ?? null);
        $radius = $this->toNullableFloat($settings['radius_geofencing'] ?? null);

        if ($radius === null || $radius <= 0) {
            $radius = self::DEFAULT_RADIUS_METER;
        }

        return [
            'aktif' => $aktif,
            'latitude_sekolah' => $latitudeSekolah,
            'longitude_sekolah' => $longitudeSekolah,
            'radius_meter' => $radius,
        ];
    }

    /**
     * Validasi koordinat latitude/longitude device.
     *
     * @throws InvalidArgumentException
     */
    public function validasiKoordinat(
        mixed $latitude,
        mixed $longitude
    ): array {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            throw new InvalidArgumentException('Koordinat lokasi tidak valid.');
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;

        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException('Latitude harus berada antara -90 dan 90.');
        }

        if ($lng < -180 || $lng > 180) {
            throw new InvalidArgumentException('Longitude harus berada antara -180 dan 180.');
        }

        return [
            'latitude' => $lat,
            'longitude' => $lng,
        ];
    }

    /**
     * Menghitung jarak Haversine dalam meter.
     */
    public function hitungJarak(
        float $latitudeAwal,
        float $longitudeAwal,
        float $latitudeTujuan,
        float $longitudeTujuan
    ): float {
        $lat1 = deg2rad($latitudeAwal);
        $lng1 = deg2rad($longitudeAwal);
        $lat2 = deg2rad($latitudeTujuan);
        $lng2 = deg2rad($longitudeTujuan);

        $deltaLat = $lat2 - $lat1;
        $deltaLng = $lng2 - $lng1;

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($deltaLng / 2) ** 2;

        $a = min(1.0, max(0.0, $a));
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METER * $c;
    }

    /**
     * Mengecek apakah koordinat device berada dalam radius sekolah.
     */
    public function isDalamRadius(
        float $latitudeDevice,
        float $longitudeDevice,
        float $latitudeSekolah,
        float $longitudeSekolah,
        float $radiusMeter
    ): bool {
        if ($radiusMeter <= 0) {
            throw new InvalidArgumentException('Radius geofencing harus lebih besar dari 0 meter.');
        }

        return $this->hitungJarak(
            $latitudeDevice,
            $longitudeDevice,
            $latitudeSekolah,
            $longitudeSekolah
        ) <= $radiusMeter;
    }

    /**
     * Validasi geofence lengkap untuk actor yang memang wajib geofence.
     *
     * Bila geofencing global OFF, method selalu mengizinkan tanpa koordinat.
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     aktif: bool,
     *     distance_meter: ?float,
     *     radius_meter: float
     * }
     */
    public function validateRequired(
        mixed $latitude,
        mixed $longitude
    ): array {
        $config = $this->getConfig();

        if (! $config['aktif']) {
            return [
                'success' => true,
                'message' => 'Geofencing sedang tidak aktif.',
                'aktif' => false,
                'distance_meter' => null,
                'radius_meter' => $config['radius_meter'],
            ];
        }

        if (
            $config['latitude_sekolah'] === null
            || $config['longitude_sekolah'] === null
        ) {
            return [
                'success' => false,
                'message' => 'Koordinat sekolah belum dikonfigurasi.',
                'aktif' => true,
                'distance_meter' => null,
                'radius_meter' => $config['radius_meter'],
            ];
        }

        try {
            $device = $this->validasiKoordinat($latitude, $longitude);
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'aktif' => true,
                'distance_meter' => null,
                'radius_meter' => $config['radius_meter'],
            ];
        }

        $distance = $this->hitungJarak(
            $device['latitude'],
            $device['longitude'],
            $config['latitude_sekolah'],
            $config['longitude_sekolah']
        );

        if ($distance > $config['radius_meter']) {
            return [
                'success' => false,
                'message' => sprintf(
                    'Lokasi berada di luar radius sekolah (%.0f m dari sekolah, batas %.0f m).',
                    $distance,
                    $config['radius_meter']
                ),
                'aktif' => true,
                'distance_meter' => $distance,
                'radius_meter' => $config['radius_meter'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Lokasi berada dalam radius sekolah.',
            'aktif' => true,
            'distance_meter' => $distance,
            'radius_meter' => $config['radius_meter'],
        ];
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on', 'aktif'], true);
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
