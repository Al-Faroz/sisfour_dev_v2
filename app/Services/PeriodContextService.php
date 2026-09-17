<?php

namespace App\Services;

use App\Models\TahunAjaranModel;

/**
 * Shared period context for periodic/history tables.
 *
 * Read surfaces may select historical periods. New operational mutations must
 * still snapshot the active period explicitly in their owning Service.
 */
class PeriodContextService
{
    protected TahunAjaranModel $model;

    public function __construct()
    {
        $this->model = new TahunAjaranModel();
    }

    public function active(): ?array
    {
        $row = $this->model->getAktif();

        return $row ?: null;
    }

    public function options(): array
    {
        return $this->model
            ->orderBy('nama_tahun', 'DESC')
            ->orderBy("FIELD(semester, 'Ganjil', 'Genap')", '', false)
            ->findAll();
    }

    /**
     * Resolve table-filter period. Empty input means active period.
     */
    public function resolve(array $input): array
    {
        $active = $this->active();
        if ($active === null) {
            return [
                'success' => false,
                'code' => 'NO_ACTIVE_YEAR',
                'message' => 'Tidak ada Tahun Ajaran aktif.',
            ];
        }

        $options = $this->options();
        $requested = (int) ($input['id_tahun'] ?? 0);
        $selectedId = $requested > 0 ? $requested : (int) $active['id'];
        $selected = null;

        foreach ($options as $option) {
            if ((int) ($option['id'] ?? 0) === $selectedId) {
                $selected = $option;
                break;
            }
        }

        if ($selected === null) {
            return [
                'success' => false,
                'code' => 'INVALID_PERIOD',
                'message' => 'Tahun Ajaran yang dipilih tidak valid.',
            ];
        }

        return [
            'success' => true,
            'active' => $active,
            'selected' => $selected,
            'options' => $options,
        ];
    }
}
