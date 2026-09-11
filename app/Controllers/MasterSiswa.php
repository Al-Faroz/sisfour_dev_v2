<?php

namespace App\Controllers;

use App\Services\MasterPaginationService;
use App\Services\SiswaImportService;
use App\Services\SiswaService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MasterSiswa extends BaseController
{
    protected SiswaService $siswaService;
    protected SiswaImportService $siswaImportService;
    protected MasterPaginationService $paginationService;

    public function __construct()
    {
        $this->siswaService = new SiswaService();
        $this->siswaImportService = new SiswaImportService();
        $this->paginationService = new MasterPaginationService();
    }

    public function index()
    {
        $userId = (int) session()->get('user_id');
        $filter = $this->filters();

        if ($this->isJsonRequest()) {
            $paging = $this->paginationService->normalizePaging(
                $this->request->getGet()
            );

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->paginationService->pageSiswa(
                    $filter,
                    $userId,
                    $paging['limit'],
                    $paging['offset']
                ),
            ]);
        }

        $editScope = $this->siswaService->getEditScope($userId);
        $manageScope = $this->siswaService->getManageScope($userId);
        $importExportScope = $this->siswaService->getImportExportScope($userId);

        return $this->response->setBody(
            $this->renderWithLayout('master/siswa', [
                'title' => 'Master Siswa',
                'filters' => $filter,
                'kelasOptions' => $this->siswaService->getKelasOptions($userId),
                'canEdit' => $editScope !== 'TIDAK_ADA',
                'canEditNisn' => $editScope === 'SEMUA',
                'canManage' => $manageScope === 'SEMUA',
                'canImportExport' => $importExportScope === 'SEMUA',
                'extraJs' => ['assets/js/master/siswa.js'],
            ])
        );
    }

    public function recycle()
    {
        $userId = (int) session()->get('user_id');

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $this->siswaService->getList(
                    $this->filters(),
                    $userId,
                    true
                ),
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('master/siswa_recycle', [
                'title' => 'Recycle Bin Siswa',
                'extraJs' => ['assets/js/master/siswa-recycle.js'],
            ])
        );
    }

    public function create()
    {
        return $this->respondResult(
            $this->siswaService->create(
                $this->request->getPost(),
                $this->request->getFile('foto')
            ),
            201
        );
    }

    public function update($id)
    {
        $payload = $this->request->getRawInput();

        if ($payload === []) {
            $json = $this->request->getJSON(true);
            $payload = is_array($json) ? $json : [];
        }

        return $this->respondResult(
            $this->siswaService->update(
                (int) $id,
                $payload,
                (int) session()->get('user_id')
            )
        );
    }

    public function uploadFoto($id)
    {
        $foto = $this->request->getFile('foto');

        if ($foto === null) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'File foto wajib dipilih.',
                ]);
        }

        return $this->respondResult(
            $this->siswaService->uploadFoto(
                (int) $id,
                $foto,
                (int) session()->get('user_id')
            )
        );
    }

    public function delete($id)
    {
        return $this->respondResult(
            $this->siswaService->delete((int) $id)
        );
    }

    public function restore($id)
    {
        return $this->respondResult(
            $this->siswaService->restore((int) $id)
        );
    }

    public function forceDelete($id)
    {
        return $this->respondResult(
            $this->siswaService->forceDelete((int) $id)
        );
    }

    public function import()
    {
        $file = $this->request->getFile('file');

        if ($file === null) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'File Excel wajib dipilih.',
                ]);
        }

        return $this->respondResult(
            $this->siswaImportService->import(
                $file,
                (int) session()->get('user_id')
            )
        );
    }

    public function downloadTemplate()
    {
        $userId = (int) session()->get('user_id');
        $kelasOptions = $this->siswaService->getKelasOptions($userId);

        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DATA_SISWA');

        $sheet->fromArray([
            [
                'NIK',
                'NISN',
                'NAMA LENGKAP',
                'JENIS KELAMIN',
                'TEMPAT LAHIR',
                'TANGGAL LAHIR',
                'ALAMAT',
                'NAMA KELAS',
            ],
            [
                '3510123412341234',
                '9876543210',
                'Ahmad Fauzi',
                'L',
                'Jombang',
                '2008-01-15',
                'Jl. Merdeka No. 10',
                $kelasOptions[0]['nama_kelas'] ?? '',
            ],
        ], null, 'A1');

        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A:B')->getNumberFormat()->setFormatCode('@');

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $kelasSheet = $spreadsheet->createSheet();
        $kelasSheet->setTitle('DAFTAR_KELAS');
        $kelasSheet->fromArray([
            ['NAMA KELAS', 'TINGKAT', 'ROMBEL'],
        ], null, 'A1');
        $kelasSheet->getStyle('A1:C1')->getFont()->setBold(true);

        $row = 2;

        foreach ($kelasOptions as $kelas) {
            $kelasSheet->fromArray([[
                $kelas['nama_kelas'],
                $kelas['tingkat'],
                $kelas['rombel'],
            ]], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'C') as $column) {
            $kelasSheet->getColumnDimension($column)->setAutoSize(true);
        }

        if ($row > 2) {
            $lastRow = $row - 1;

            $spreadsheet->addNamedRange(
                new NamedRange(
                    'DAFTAR_KELAS_VALID',
                    $kelasSheet,
                    '$A$2:$A$' . $lastRow
                )
            );

            for ($dataRow = 2; $dataRow <= 2000; $dataRow++) {
                $validation = $sheet
                    ->getCell('H' . $dataRow)
                    ->getDataValidation();

                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Kelas tidak valid');
                $validation->setError(
                    'Pilih nama kelas dari daftar yang tersedia.'
                );
                $validation->setPromptTitle('Pilih kelas');
                $validation->setPrompt(
                    'Gunakan kelas yang tersedia pada sheet DAFTAR_KELAS.'
                );
                $validation->setFormula1('=DAFTAR_KELAS_VALID');
            }
        }

        $sheet->freezePane('A2');
        $kelasSheet->freezePane('A2');
        $spreadsheet->setActiveSheetIndex(0);

        $tempFile = tempnam(
            sys_get_temp_dir(),
            'sisfour_template_siswa_'
        );

        (new Xlsx($spreadsheet))->save($tempFile);

        return $this->response
            ->download($tempFile, null)
            ->setFileName('template_import_siswa.xlsx');
    }

    public function export()
    {
        $userId = (int) session()->get('user_id');
        $data = $this->siswaService->getList(
            $this->filters(),
            $userId
        );

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');

        $sheet->setCellValue('A1', 'DATA SISWA SISISFOUR');
        $sheet->mergeCells('A1:Q1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->fromArray([[
            'NIK',
            'NISN',
            'NAMA',
            'JK',
            'TEMPAT LAHIR',
            'TANGGAL LAHIR',
            'ALAMAT',
            'NO. TELEPON',
            'KEBUTUHAN KHUSUS',
            'DISABILITAS',
            'NO. KIP/PIP',
            'NAMA AYAH',
            'NAMA IBU',
            'NAMA WALI',
            'KELAS',
            'STATUS',
            'TANGGAL MUTASI',
        ]], null, 'A3');

        $row = 4;

        foreach ($data as $siswa) {
            $sheet->fromArray([[
                $siswa['nik'],
                $siswa['nisn'],
                $siswa['nama'],
                $siswa['jenis_kelamin'],
                $siswa['tempat_lahir'],
                $siswa['tanggal_lahir'],
                $siswa['alamat'],
                $siswa['no_telepon'],
                $siswa['kebutuhan_khusus'],
                $siswa['disabilitas'],
                $siswa['nomor_kip_pip'],
                $siswa['nama_ayah_kandung'],
                $siswa['nama_ibu_kandung'],
                $siswa['nama_wali'],
                $siswa['nama_kelas_aktif'],
                $siswa['status_aktif'],
                $siswa['tanggal_mutasi'],
            ]], null, 'A' . $row);

            $sheet->setCellValueExplicit(
                'A' . $row,
                (string) $siswa['nik'],
                DataType::TYPE_STRING
            );

            $sheet->setCellValueExplicit(
                'B' . $row,
                (string) $siswa['nisn'],
                DataType::TYPE_STRING
            );

            $row++;
        }

        $sheet->getStyle('A3:Q3')->getFont()->setBold(true);

        foreach (range('A', 'Q') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $tempFile = tempnam(
            sys_get_temp_dir(),
            'sisfour_export_siswa_'
        );

        (new Xlsx($spreadsheet))->save($tempFile);

        return $this->response
            ->download($tempFile, null)
            ->setFileName(
                'data_siswa_' . date('Ymd_His') . '.xlsx'
            );
    }

    protected function filters(): array
    {
        return [
            'nama' => trim((string) $this->request->getGet('nama')),
            'nik' => trim((string) $this->request->getGet('nik')),
            'nisn' => trim((string) $this->request->getGet('nisn')),
            'id_kelas' => (int) $this->request->getGet('id_kelas'),
            'status_aktif' => trim(
                (string) $this->request->getGet('status_aktif')
            ),
        ];
    }

    protected function isJsonRequest(): bool
    {
        $path = trim($this->request->getUri()->getPath(), '/');

        return str_ends_with($path, '/json')
            || $this->request->getGet('format') === 'json'
            || $this->request->isAJAX();
    }

    protected function respondResult(
        array $result,
        int $successCode = 200
    ) {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode($success ? $successCode : 422)
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message']
                    ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
