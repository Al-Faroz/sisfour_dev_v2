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
 * Penghapusan akan ditolak oleh FK RESTRICT/NO ACTION bila mapel sudah
 * digunakan pada jadwal_guru.
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
        'kode_mapel' => 'required|max_length[10]|is_unique[mata_pelajaran.kode_mapel,id,{id}]',
    ];

    protected $validationMessages = [
        'nama_mapel' => [
            'required' => 'Nama mata pelajaran wajib diisi.',
        ],
        'kode_mapel' => [
            'required'  => 'Kode mata pelajaran wajib diisi.',
            'is_unique' => 'Kode mata pelajaran sudah digunakan.',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Cari mapel berdasarkan kode.
     */
    public function findByKode(string $kodeMapel): ?array
    {
        return $this
            ->where('kode_mapel', trim($kodeMapel))
            ->first();
    }

    /**
     * Daftar mapel terurut untuk dropdown/list.
     */
    public function getTerurut(): array
    {
        return $this
            ->orderBy('nama_mapel', 'ASC')
            ->findAll();
    }
}
