<?php

namespace App\Services;

use App\Models\GuruModel;
use App\Models\SiswaModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use DateTimeImmutable;
use Throwable;

class ProfileService
{
    protected BaseConnection $db;
    protected GuruModel $guruModel;
    protected SiswaModel $siswaModel;
    protected UploadService $uploadService;
    protected ActivityLogService $activityLog;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->guruModel = new GuruModel();
        $this->siswaModel = new SiswaModel();
        $this->uploadService = new UploadService();
        $this->activityLog = new ActivityLogService();
    }

    public function getGuruProfile(int $actorUserId): array
    {
        $identity = $this->guruIdentity($actorUserId);

        if (!$identity['success']) {
            return $identity;
        }

        $guru = $this->guruModel->find((int) $identity['id_guru']);

        if ($guru === null) {
            return $this->fail(
                'PROFILE_NOT_FOUND',
                'Data Guru yang terhubung dengan akun tidak ditemukan.'
            );
        }

        return [
            'success' => true,
            'message' => 'Profile Guru berhasil dimuat.',
            'profile' => $this->publicGuru($guru),
        ];
    }

    public function updateGuruProfile(
        int $actorUserId,
        array $input
    ): array {
        $identity = $this->guruIdentity($actorUserId);

        if (!$identity['success']) {
            return $identity;
        }

        $idGuru = (int) $identity['id_guru'];
        $guru = $this->guruModel->find($idGuru);

        if ($guru === null) {
            return $this->fail(
                'PROFILE_NOT_FOUND',
                'Data Guru yang terhubung dengan akun tidak ditemukan.'
            );
        }

        $payload = [
            'nama' => trim((string) ($input['nama'] ?? '')),
            'jenis_kelamin' => strtoupper(
                trim((string) ($input['jenis_kelamin'] ?? ''))
            ),
            'tempat_lahir' => $this->nullableText(
                $input['tempat_lahir'] ?? null,
                100
            ),
            'tanggal_lahir' => $this->nullableDate(
                $input['tanggal_lahir'] ?? null
            ),
            'alamat' => $this->nullableText(
                $input['alamat'] ?? null,
                5000
            ),
            'no_telepon' => $this->nullableText(
                $input['no_telepon'] ?? null,
                20
            ),
            'email' => $this->nullableText(
                $input['email'] ?? null,
                100
            ),
        ];

        $validation = $this->validateGuruPayload($payload);

        if ($validation !== null) {
            return $validation;
        }

        $this->db->transBegin();

        try {
            if (!$this->guruModel->update($idGuru, $payload)) {
                throw new \RuntimeException(
                    implode(' ', $this->guruModel->errors())
                    ?: 'Profile Guru gagal diperbarui.'
                );
            }

            $this->activityLog->write(
                $actorUserId,
                'UPDATE_PROFILE',
                'Profile Guru',
                sprintf(
                    'Memperbarui profile Guru ID %d - %s.',
                    $idGuru,
                    $payload['nama']
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Profile Guru berhasil diperbarui.',
                'profile' => $this->publicGuru(
                    $this->guruModel->find($idGuru) ?? array_merge($guru, $payload)
                ),
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return $this->fail(
                'UPDATE_FAILED',
                $e->getMessage()
            );
        }
    }

    public function uploadGuruFoto(
        int $actorUserId,
        ?UploadedFile $foto
    ): array {
        $identity = $this->guruIdentity($actorUserId);

        if (!$identity['success']) {
            return $identity;
        }

        if (
            $foto === null
            || $foto->getError() === UPLOAD_ERR_NO_FILE
        ) {
            return $this->fail(
                'VALIDATION',
                'File foto wajib dipilih.'
            );
        }

        $idGuru = (int) $identity['id_guru'];
        $guru = $this->guruModel->find($idGuru);

        if ($guru === null) {
            return $this->fail(
                'PROFILE_NOT_FOUND',
                'Data Guru yang terhubung dengan akun tidak ditemukan.'
            );
        }

        $newFilename = null;
        $destDir = rtrim(
            FCPATH . 'uploads/foto_guru',
            DIRECTORY_SEPARATOR
        );

        try {
            $newFilename = $this->uploadService->processFotoPortrait(
                $foto,
                $destDir,
                'guru_' . preg_replace(
                    '/[^A-Za-z0-9_-]/',
                    '_',
                    (string) $guru['nip']
                )
            );

            $this->db->transBegin();

            if (!$this->guruModel->update(
                $idGuru,
                ['foto' => $newFilename]
            )) {
                throw new \RuntimeException(
                    'Foto Profile Guru gagal disimpan.'
                );
            }

            $this->activityLog->write(
                $actorUserId,
                'UPDATE_FOTO',
                'Profile Guru',
                sprintf(
                    'Mengganti foto profile Guru ID %d - %s.',
                    $idGuru,
                    $guru['nama']
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            $this->deleteOldFoto(
                $destDir,
                (string) ($guru['foto'] ?? ''),
                $newFilename
            );

            return [
                'success' => true,
                'message' => 'Foto Profile Guru berhasil diperbarui.',
                'foto' => $newFilename,
            ];
        } catch (Throwable $e) {
            if ($this->db->transStatus() !== false) {
                $this->db->transRollback();
            } else {
                $this->db->transRollback();
            }

            if ($newFilename !== null) {
                $newPath = $destDir
                    . DIRECTORY_SEPARATOR
                    . basename($newFilename);

                if (is_file($newPath)) {
                    @unlink($newPath);
                }
            }

            return $this->fail(
                'UPLOAD_FAILED',
                $e->getMessage()
            );
        }
    }

    public function getSiswaProfile(int $actorUserId): array
    {
        $identity = $this->siswaIdentity($actorUserId);

        if (!$identity['success']) {
            return $identity;
        }

        $idSiswa = (int) $identity['id_siswa'];

        $siswa = $this->db
            ->table('siswa s')
            ->select(
                's.id, s.nik, s.nisn, s.nama, s.jenis_kelamin, ' .
                's.tempat_lahir, s.tanggal_lahir, s.alamat, s.no_telepon, ' .
                's.kebutuhan_khusus, s.disabilitas, s.nomor_kip_pip, ' .
                's.nama_ayah_kandung, s.nama_ibu_kandung, s.nama_wali, ' .
                's.foto, s.status_aktif, s.tanggal_mutasi, ' .
                's.keterangan_mutasi'
            )
            ->where('s.id', $idSiswa)
            ->where('s.deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$siswa) {
            return $this->fail(
                'PROFILE_NOT_FOUND',
                'Data Siswa yang terhubung dengan akun tidak ditemukan.'
            );
        }

        $kelasAktif = $this->currentStudentClass($idSiswa);
        $riwayat = $this->studentClassHistory($idSiswa);

        $siswa['kelas_aktif'] = $kelasAktif;
        $siswa['riwayat_kelas'] = $riwayat;

        return [
            'success' => true,
            'message' => 'Profile Siswa berhasil dimuat.',
            'profile' => $siswa,
        ];
    }

    private function guruIdentity(int $actorUserId): array
    {
        if ($actorUserId <= 0) {
            return $this->fail(
                'UNAUTHENTICATED',
                'Identitas pengguna tidak valid.'
            );
        }

        $user = $this->db
            ->table('users')
            ->select('id, id_guru, status_aktif')
            ->where('id', $actorUserId)
            ->get()
            ->getRowArray();

        if (!$user || (int) ($user['status_aktif'] ?? 0) !== 1) {
            return $this->fail(
                'UNAUTHENTICATED',
                'Akun tidak aktif atau tidak ditemukan.'
            );
        }

        $idGuru = (int) ($user['id_guru'] ?? 0);

        if ($idGuru <= 0) {
            return $this->fail(
                'IDENTITY_NOT_LINKED',
                'Akun ini tidak terhubung dengan identitas Guru.'
            );
        }

        return [
            'success' => true,
            'id_guru' => $idGuru,
        ];
    }

    private function siswaIdentity(int $actorUserId): array
    {
        if ($actorUserId <= 0) {
            return $this->fail(
                'UNAUTHENTICATED',
                'Identitas pengguna tidak valid.'
            );
        }

        $user = $this->db
            ->table('users')
            ->select('id, id_siswa, status_aktif')
            ->where('id', $actorUserId)
            ->get()
            ->getRowArray();

        if (!$user || (int) ($user['status_aktif'] ?? 0) !== 1) {
            return $this->fail(
                'UNAUTHENTICATED',
                'Akun tidak aktif atau tidak ditemukan.'
            );
        }

        $idSiswa = (int) ($user['id_siswa'] ?? 0);

        if ($idSiswa <= 0) {
            return $this->fail(
                'IDENTITY_NOT_LINKED',
                'Akun ini tidak terhubung dengan identitas Siswa.'
            );
        }

        return [
            'success' => true,
            'id_siswa' => $idSiswa,
        ];
    }

    private function validateGuruPayload(array $payload): ?array
    {
        if ($payload['nama'] === '') {
            return $this->fail(
                'VALIDATION',
                'Nama Guru wajib diisi.'
            );
        }

        if (mb_strlen($payload['nama']) > 150) {
            return $this->fail(
                'VALIDATION',
                'Nama Guru maksimal 150 karakter.'
            );
        }

        if (!in_array(
            $payload['jenis_kelamin'],
            ['L', 'P'],
            true
        )) {
            return $this->fail(
                'VALIDATION',
                'Jenis kelamin harus L atau P.'
            );
        }

        if (
            $payload['email'] !== null
            && !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)
        ) {
            return $this->fail(
                'VALIDATION',
                'Format email tidak valid.'
            );
        }

        if (
            $payload['tanggal_lahir'] === false
        ) {
            return $this->fail(
                'VALIDATION',
                'Tanggal lahir harus menggunakan format YYYY-MM-DD.'
            );
        }

        return null;
    }

    private function nullableText(
        mixed $value,
        int $maxLength
    ): ?string {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $maxLength);
    }

    private function nullableDate(mixed $value): string|null|false
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value
        );

        $errors = DateTimeImmutable::getLastErrors();

        if (
            !$date
            || (
                is_array($errors)
                && (
                    ($errors['warning_count'] ?? 0) > 0
                    || ($errors['error_count'] ?? 0) > 0
                )
            )
            || $date->format('Y-m-d') !== $value
        ) {
            return false;
        }

        return $value;
    }

    private function currentStudentClass(int $idSiswa): ?array
    {
        $tahun = $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$tahun) {
            return null;
        }

        return $this->db
            ->table('anggota_kelas ak')
            ->select(
                'ak.id_kelas, k.nama_kelas, k.tingkat, k.rombel, ' .
                'ta.id AS id_tahun, ta.nama_tahun, ta.semester'
            )
            ->join('kelas k', 'k.id = ak.id_kelas')
            ->join('tahun_ajaran ta', 'ta.id = ak.id_tahun')
            ->where('ak.id_siswa', $idSiswa)
            ->where('ak.id_tahun', (int) $tahun['id'])
            ->where('k.deleted_at', null)
            ->get()
            ->getRowArray();
    }

    private function studentClassHistory(int $idSiswa): array
    {
        return $this->db
            ->table('riwayat_siswa rs')
            ->select(
                'rs.id, rs.status, rs.tanggal_mulai, rs.tanggal_selesai, ' .
                'rs.keterangan, k.nama_kelas, ta.nama_tahun, ta.semester'
            )
            ->join('kelas k', 'k.id = rs.id_kelas')
            ->join('tahun_ajaran ta', 'ta.id = rs.id_tahun')
            ->where('rs.id_siswa', $idSiswa)
            ->orderBy('rs.tanggal_mulai', 'DESC')
            ->orderBy('rs.id', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();
    }

    private function publicGuru(array $guru): array
    {
        return [
            'id' => (int) $guru['id'],
            'nip' => (string) $guru['nip'],
            'nama' => (string) $guru['nama'],
            'jenis_kelamin' => (string) $guru['jenis_kelamin'],
            'tempat_lahir' => $guru['tempat_lahir'],
            'tanggal_lahir' => $guru['tanggal_lahir'],
            'alamat' => $guru['alamat'],
            'no_telepon' => $guru['no_telepon'],
            'email' => $guru['email'],
            'status_kepegawaian' => $guru['status_kepegawaian'],
            'foto' => $guru['foto'],
        ];
    }

    private function deleteOldFoto(
        string $destDir,
        string $oldFilename,
        string $newFilename
    ): void {
        $oldFilename = basename(trim($oldFilename));

        if (
            $oldFilename === ''
            || $oldFilename === basename($newFilename)
        ) {
            return;
        }

        $oldPath = $destDir
            . DIRECTORY_SEPARATOR
            . $oldFilename;

        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    private function fail(string $code, string $message): array
    {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
    }
}
