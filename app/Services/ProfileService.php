<?php

namespace App\Services;

use App\Models\GuruModel;
use App\Models\PegawaiModel;
use App\Models\SiswaModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use DateTimeImmutable;
use Throwable;

/**
 * Self-service profile.
 *
 * NIK, NIP dan status kepegawaian bersifat administratif karena perubahan
 * identifier dapat mengubah kredensial login. Field tersebut hanya dibaca
 * dari Profile dan dikelola melalui Master Guru/Pegawai oleh actor berhak.
 */
class ProfileService
{
    protected BaseConnection $db;
    protected GuruModel $guruModel;
    protected PegawaiModel $pegawaiModel;
    protected SiswaModel $siswaModel;
    protected UploadService $uploadService;
    protected ActivityLogService $activityLog;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->guruModel = new GuruModel();
        $this->pegawaiModel = new PegawaiModel();
        $this->siswaModel = new SiswaModel();
        $this->uploadService = new UploadService();
        $this->activityLog = new ActivityLogService();
    }

    public function getGuruProfile(int $actorUserId): array
    {
        $identity = $this->identity($actorUserId, 'id_guru', 'Guru');
        if (! $identity['success']) {
            return $identity;
        }

        $guru = $this->guruModel->find((int) $identity['identity_id']);
        if ($guru === null) {
            return $this->fail('PROFILE_NOT_FOUND', 'Data Guru yang terhubung dengan akun tidak ditemukan.');
        }

        return [
            'success' => true,
            'message' => 'Profile Guru berhasil dimuat.',
            'profile' => $this->publicPersonalia($guru, 'guru'),
        ];
    }

    public function updateGuruProfile(int $actorUserId, array $input): array
    {
        return $this->updatePersonaliaProfile(
            $actorUserId,
            $input,
            'id_guru',
            'Guru',
            $this->guruModel
        );
    }

    public function uploadGuruFoto(int $actorUserId, ?UploadedFile $foto): array
    {
        return $this->uploadPersonaliaFoto(
            $actorUserId,
            $foto,
            'id_guru',
            'Guru',
            $this->guruModel,
            'uploads/foto_guru',
            'guru'
        );
    }

    public function getPegawaiProfile(int $actorUserId): array
    {
        $identity = $this->identity($actorUserId, 'id_pegawai', 'Pegawai');
        if (! $identity['success']) {
            return $identity;
        }

        $pegawai = $this->pegawaiModel->find((int) $identity['identity_id']);
        if ($pegawai === null) {
            return $this->fail('PROFILE_NOT_FOUND', 'Data Pegawai yang terhubung dengan akun tidak ditemukan.');
        }

        return [
            'success' => true,
            'message' => 'Profile Pegawai berhasil dimuat.',
            'profile' => $this->publicPersonalia($pegawai, 'pegawai'),
        ];
    }

    public function updatePegawaiProfile(int $actorUserId, array $input): array
    {
        return $this->updatePersonaliaProfile(
            $actorUserId,
            $input,
            'id_pegawai',
            'Pegawai',
            $this->pegawaiModel
        );
    }

    public function uploadPegawaiFoto(int $actorUserId, ?UploadedFile $foto): array
    {
        return $this->uploadPersonaliaFoto(
            $actorUserId,
            $foto,
            'id_pegawai',
            'Pegawai',
            $this->pegawaiModel,
            'uploads/foto_pegawai',
            'pegawai'
        );
    }

    private function updatePersonaliaProfile(
        int $actorUserId,
        array $input,
        string $identityField,
        string $label,
        GuruModel|PegawaiModel $model
    ): array {
        $identity = $this->identity($actorUserId, $identityField, $label);
        if (! $identity['success']) {
            return $identity;
        }

        $id = (int) $identity['identity_id'];
        $current = $model->find($id);

        if ($current === null) {
            return $this->fail('PROFILE_NOT_FOUND', 'Data ' . $label . ' yang terhubung dengan akun tidak ditemukan.');
        }

        $payload = [
            'nama' => trim((string) ($input['nama'] ?? '')),
            'jenis_kelamin' => strtoupper(trim((string) ($input['jenis_kelamin'] ?? ''))),
            'tempat_lahir' => $this->nullableText($input['tempat_lahir'] ?? null, 100),
            'tanggal_lahir' => $this->nullableDate($input['tanggal_lahir'] ?? null),
            'agama' => $this->nullableText($input['agama'] ?? null, 30),
            'nuptk' => $this->nullableIdentifier($input['nuptk'] ?? null),
            'alamat' => $this->nullableText($input['alamat'] ?? null, 5000),
            'no_telepon' => $this->nullableText($input['no_telepon'] ?? null, 20),
            'email' => $this->nullableText($input['email'] ?? null, 100),
        ];

        $validation = $this->validatePersonalPayload($payload, $label);
        if ($validation !== null) {
            return $validation;
        }

        $this->db->transBegin();

        try {
            if (! $model->update($id, $payload)) {
                throw new \RuntimeException(
                    implode(' ', $model->errors()) ?: 'Profile ' . $label . ' gagal diperbarui.'
                );
            }

            $this->activityLog->write(
                $actorUserId,
                'UPDATE_PROFILE',
                'Profile ' . $label,
                sprintf('Memperbarui profile %s ID %d - %s.', $label, $id, $payload['nama'])
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            $fresh = $model->find($id) ?? array_merge($current, $payload);

            return [
                'success' => true,
                'message' => 'Profile ' . $label . ' berhasil diperbarui.',
                'profile' => $this->publicPersonalia(
                    $fresh,
                    $identityField === 'id_guru' ? 'guru' : 'pegawai'
                ),
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail('UPDATE_FAILED', $e->getMessage());
        }
    }

    private function uploadPersonaliaFoto(
        int $actorUserId,
        ?UploadedFile $foto,
        string $identityField,
        string $label,
        GuruModel|PegawaiModel $model,
        string $relativeDir,
        string $prefix
    ): array {
        $identity = $this->identity($actorUserId, $identityField, $label);
        if (! $identity['success']) {
            return $identity;
        }

        if ($foto === null || $foto->getError() === UPLOAD_ERR_NO_FILE) {
            return $this->fail('VALIDATION', 'File foto wajib dipilih.');
        }

        $id = (int) $identity['identity_id'];
        $current = $model->find($id);

        if ($current === null) {
            return $this->fail('PROFILE_NOT_FOUND', 'Data ' . $label . ' yang terhubung dengan akun tidak ditemukan.');
        }

        $destDir = rtrim(FCPATH . $relativeDir, DIRECTORY_SEPARATOR);
        $login = $this->loginIdentifier($current);
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', $login !== '' ? $login : (string) $id) ?: (string) $id;
        $newFilename = null;

        try {
            $newFilename = $this->uploadService->processFotoPortrait(
                $foto,
                $destDir,
                $prefix . '_' . $safe
            );

            $this->db->transBegin();

            if (! $model->update($id, ['foto' => $newFilename])) {
                throw new \RuntimeException('Foto Profile ' . $label . ' gagal disimpan.');
            }

            $this->activityLog->write(
                $actorUserId,
                'UPDATE_FOTO',
                'Profile ' . $label,
                sprintf('Mengganti foto profile %s ID %d - %s.', $label, $id, $current['nama'])
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();
            $this->deleteOldFoto($destDir, (string) ($current['foto'] ?? ''), $newFilename);

            return [
                'success' => true,
                'message' => 'Foto Profile ' . $label . ' berhasil diperbarui.',
                'foto' => $newFilename,
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            if ($newFilename !== null) {
                $newPath = $destDir . DIRECTORY_SEPARATOR . basename($newFilename);
                if (is_file($newPath)) {
                    @unlink($newPath);
                }
            }

            return $this->fail('UPLOAD_FAILED', $e->getMessage());
        }
    }

    public function getSiswaProfile(int $actorUserId): array
    {
        $identity = $this->identity($actorUserId, 'id_siswa', 'Siswa');

        if (! $identity['success']) {
            return $identity;
        }

        $idSiswa = (int) $identity['identity_id'];

        $siswa = $this->db
            ->table('siswa s')
            ->select(
                's.id, s.nik, s.nisn, s.nama, s.jenis_kelamin, ' .
                's.tempat_lahir, s.tanggal_lahir, s.alamat, s.no_telepon, ' .
                's.kebutuhan_khusus, s.disabilitas, s.nomor_kip_pip, ' .
                's.nama_ayah_kandung, s.nama_ibu_kandung, s.nama_wali, ' .
                's.foto, s.status_aktif, s.tanggal_mutasi, s.keterangan_mutasi'
            )
            ->where('s.id', $idSiswa)
            ->where('s.deleted_at', null)
            ->get()
            ->getRowArray();

        if (! $siswa) {
            return $this->fail('PROFILE_NOT_FOUND', 'Data Siswa yang terhubung dengan akun tidak ditemukan.');
        }

        $siswa['kelas_aktif'] = $this->currentStudentClass($idSiswa);
        $siswa['riwayat_kelas'] = $this->studentClassHistory($idSiswa);

        return [
            'success' => true,
            'message' => 'Profile Siswa berhasil dimuat.',
            'profile' => $siswa,
        ];
    }

    private function identity(int $actorUserId, string $field, string $label): array
    {
        if ($actorUserId <= 0) {
            return $this->fail('UNAUTHENTICATED', 'Identitas pengguna tidak valid.');
        }

        if (! in_array($field, ['id_guru', 'id_pegawai', 'id_siswa'], true)) {
            return $this->fail('IDENTITY_NOT_LINKED', 'Jenis identitas tidak valid.');
        }

        $user = $this->db
            ->table('users')
            ->select('id, ' . $field . ', status_aktif')
            ->where('id', $actorUserId)
            ->get()
            ->getRowArray();

        if (! $user || (int) ($user['status_aktif'] ?? 0) !== 1) {
            return $this->fail('UNAUTHENTICATED', 'Akun tidak aktif atau tidak ditemukan.');
        }

        $identityId = (int) ($user[$field] ?? 0);
        if ($identityId <= 0) {
            return $this->fail(
                'IDENTITY_NOT_LINKED',
                'Akun ini tidak terhubung dengan identitas ' . $label . '.'
            );
        }

        return ['success' => true, 'identity_id' => $identityId];
    }

    private function validatePersonalPayload(array $payload, string $label): ?array
    {
        if ($payload['nama'] === '') {
            return $this->fail('VALIDATION', 'Nama ' . $label . ' wajib diisi.');
        }

        if (mb_strlen($payload['nama']) > 150) {
            return $this->fail('VALIDATION', 'Nama ' . $label . ' maksimal 150 karakter.');
        }

        if (! in_array($payload['jenis_kelamin'], ['L', 'P'], true)) {
            return $this->fail('VALIDATION', 'Jenis kelamin harus L atau P.');
        }

        if ($payload['tanggal_lahir'] === false) {
            return $this->fail('VALIDATION', 'Tanggal lahir harus menggunakan format YYYY-MM-DD.');
        }

        if ($payload['email'] !== null && ! filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->fail('VALIDATION', 'Format email tidak valid.');
        }

        if ($payload['nuptk'] !== null && ! preg_match('/^[0-9]{16}$/', $payload['nuptk'])) {
            return $this->fail('VALIDATION', 'NUPTK harus kosong atau berupa tepat 16 digit angka.');
        }

        return null;
    }

    private function publicPersonalia(array $row, string $type): array
    {
        return [
            'id' => (int) $row['id'],
            'nik' => $row['nik'] !== null ? (string) $row['nik'] : null,
            'nip' => $row['nip'] !== null ? (string) $row['nip'] : null,
            'nama' => (string) $row['nama'],
            'jenis_kelamin' => (string) $row['jenis_kelamin'],
            'tempat_lahir' => $row['tempat_lahir'],
            'tanggal_lahir' => $row['tanggal_lahir'],
            'agama' => $row['agama'] ?? null,
            'alamat' => $row['alamat'],
            'no_telepon' => $row['no_telepon'],
            'email' => $row['email'],
            'status_kepegawaian' => $row['status_kepegawaian'] ?? null,
            'nuptk' => $row['nuptk'] ?? null,
            'foto' => $row['foto'] ?? null,
            'login_identifier' => $this->loginIdentifier($row),
            'identity_complete' => (bool) preg_match('/^[0-9]{16}$/', trim((string) ($row['nik'] ?? ''))),
            'jabatan_legacy' => $type === 'pegawai' ? ($row['jabatan'] ?? null) : null,
        ];
    }

    private function loginIdentifier(array $row): string
    {
        $nip = trim((string) ($row['nip'] ?? ''));
        return $nip !== '' ? $nip : trim((string) ($row['nik'] ?? ''));
    }

    private function nullableIdentifier(mixed $value): ?string
    {
        $value = preg_replace('/\s+/u', '', trim((string) $value)) ?? '';
        return $value !== '' ? $value : null;
    }

    private function nullableText(mixed $value, int $maxLength): ?string
    {
        $value = trim((string) $value);
        return $value !== '' ? mb_substr($value, 0, $maxLength) : null;
    }

    private function nullableDate(mixed $value): string|null|false
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if (
            ! $date
            || (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0))
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

        if (! $tahun) {
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

    private function deleteOldFoto(string $destDir, string $oldFilename, string $newFilename): void
    {
        $oldFilename = basename(trim($oldFilename));
        if ($oldFilename === '' || $oldFilename === basename($newFilename)) {
            return;
        }

        $oldPath = $destDir . DIRECTORY_SEPARATOR . $oldFilename;
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    private function fail(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
