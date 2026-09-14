<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * MataPelajaranModel
 *
 * Master Mata Pelajaran.
 *
 * Acuan:
 * - docs/02_DATABASE
 * - docs/04_MASTER_DATA §3.4
 *
 * Tabel mata_pelajaran menggunakan hard delete.
 * Penghapusan akan ditolak oleh FK RESTRICT/NO ACTION
 * bila mapel sudah digunakan pada jadwal_guru.
 *
 * Uniqueness kode_mapel dijaga oleh MataPelajaranService agar update dapat
 * mengecualikan record yang sedang diedit secara eksplisit.
 */
class MataPelajaranModel extends Model
{
    protected $table            = 'mata_pelajaran';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;

    protected $allowedFields = [
        'nama_mapel',
        'kode_mapel',
    ];

    protected $validationRules = [
        'nama_mapel' => 'required|max_length[100]',
        'kode_mapel' => 'required|max_length[10]',
    ];

    protected $validationMessages = [
        'nama_mapel' => [
            'required' => 'Nama mata pelajaran wajib diisi.',
        ],
        'kode_mapel' => [
            'required' => 'Kode mata pelajaran wajib diisi.',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    public function findByKode(string $kodeMapel): ?array
    {
        return $this
            ->where('kode_mapel', strtoupper(trim($kodeMapel)))
            ->first();
    }

    public function getTerurut(): array
    {
        return $this
            ->orderBy('nama_mapel', 'ASC')
            ->findAll();
    }

    public function kodeDipakai(
        string $kodeMapel,
        ?int $exceptId = null
    ): bool {
        $builder = $this->db
            ->table('mata_pelajaran')
            ->where('kode_mapel', strtoupper(trim($kodeMapel)));

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        return $builder->countAllResults() > 0;
    }
}
