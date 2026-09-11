<?php

namespace App\Controllers;

use App\Models\SettingSistemModel;
use App\Services\AuthService;
use App\Services\MenuService;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class BaseController extends Controller
{
    protected $helpers = ['url', 'form'];
    protected AuthService $authService;
    protected MenuService $menuService;
    protected array $layoutData = [];

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->authService = new AuthService();
        $this->menuService = new MenuService();

        if (session()->get('logged_in')) {
            $this->prepareLayoutData();
        }
    }

    /**
     * Actor tunggal untuk controller yang dapat dipanggil Web maupun API.
     *
     * Web  -> session user_id.
     * API  -> apiUser yang sudah divalidasi AuthFilter/JwtService.
     */
    protected function currentActorUserId(): int
    {
        if ($this->requestIsApi()) {
            $apiUser = $this->request->apiUser ?? null;

            return is_array($apiUser)
                ? (int) ($apiUser['id'] ?? 0)
                : 0;
        }

        return (int) session()->get('user_id');
    }

    /**
     * Menentukan request API dari path, bukan dari header client.
     */
    protected function requestIsApi(): bool
    {
        $path = trim($this->request->getUri()->getPath(), '/');

        return $path === 'api' || str_starts_with($path, 'api/');
    }

    /**
     * Kontrak response:
     * - seluruh route /api selalu JSON;
     * - route Web tetap dapat meminta JSON melalui /json, format=json, atau AJAX.
     */
    protected function requestWantsJson(): bool
    {
        if ($this->requestIsApi()) {
            return true;
        }

        $path = trim($this->request->getUri()->getPath(), '/');

        return str_ends_with($path, '/json')
            || $this->request->getGet('format') === 'json'
            || $this->request->isAJAX();
    }

    protected function prepareLayoutData(): void
    {
        $userId = (int) session()->get('user_id');
        $role = session()->get('role');
        $username = trim((string) session()->get('username'));
        $idGuru = session()->get('id_guru');

        $tree = $this->menuService->getMenuTree($userId);
        $tree = $this->pruneEmptyMenuGroups($tree);

        $currentPath = trim(current_url(true)->getPath(), '/');
        $tree = $this->menuService->markActive($tree, $currentPath);

        $displayIdentity = $this->resolveDisplayIdentity($userId, $username);
        $effectiveRoles = $this->authService->getUserRoles($userId);
        $experienceRole = $this->resolveExperienceRole($effectiveRoles);
        $isWali = $idGuru
            ? $this->authService->isWaliKelas((int) $idGuru)
            : false;

        $roleLabel = [
            'admin' => 'Admin',
            'operator' => 'Operator',
            'pimpinan' => 'Pimpinan',
            'bk' => 'BK',
            'guru' => $isWali ? 'Wali Kelas' : 'Guru',
            'siswa' => 'Siswa',
        ][$experienceRole] ?? '-';

        $this->layoutData = [
            'menuTree' => $tree,
            'authUser' => [
                'username' => $username,
                'display_name' => $displayIdentity['name'],
                'display_initial' => $displayIdentity['initial'],
                'identity_source' => $displayIdentity['source'],
                'role' => $role,
                'effective_roles' => $effectiveRoles,
                'experience_role' => $experienceRole,
                'display_role' => $roleLabel,
                'id_guru' => $idGuru,
                'id_pegawai' => session()->get('id_pegawai'),
                'id_siswa' => session()->get('id_siswa'),
                'is_wali' => $isWali,
            ],
            'systemSettings' => $this->loadSystemSettings(),
        ];
    }

    protected function renderWithLayout(string $view, array $data = []): string
    {
        return view($view, array_merge($this->layoutData, $data));
    }

    protected function pruneEmptyMenuGroups(array $tree): array
    {
        $result = [];

        foreach ($tree as $item) {
            $children = $this->pruneEmptyMenuGroups($item['children'] ?? []);
            $item['children'] = $children;

            $isGroup = empty($item['link']) || $item['link'] === '#';

            if ($isGroup && $children === []) {
                continue;
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Priority experience role.
     *
     * BK ditempatkan di atas Guru karena akun BK yang juga beridentitas Guru
     * otomatis mempertahankan effective role Guru. Tanpa prioritas ini,
     * dashboard/navbar BK akan salah jatuh ke experience Guru.
     */
    protected function resolveExperienceRole(array $roles): string
    {
        foreach (
            ['admin', 'operator', 'pimpinan', 'bk', 'guru', 'siswa']
            as $role
        ) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return 'guru';
    }

    /**
     * Resolve nama tampilan global untuk shell/navbar.
     *
     * Prioritas:
     * 1. guru.nama
     * 2. pegawai.nama
     * 3. siswa.nama
     * 4. fallback username untuk akun sistem murni.
     *
     * @return array{name:string,initial:string,source:string}
     */
    private function resolveDisplayIdentity(int $userId, string $username): array
    {
        $displayName = $username !== '' ? $username : 'User';
        $source = 'username';

        if ($userId > 0) {
            try {
                $row = Database::connect()
                    ->table('users u')
                    ->select([
                        'g.nama AS guru_nama',
                        'p.nama AS pegawai_nama',
                        's.nama AS siswa_nama',
                    ])
                    ->join(
                        'guru g',
                        'g.id = u.id_guru AND g.deleted_at IS NULL',
                        'left'
                    )
                    ->join(
                        'pegawai p',
                        'p.id = u.id_pegawai AND p.deleted_at IS NULL',
                        'left'
                    )
                    ->join(
                        'siswa s',
                        's.id = u.id_siswa AND s.deleted_at IS NULL',
                        'left'
                    )
                    ->where('u.id', $userId)
                    ->get()
                    ->getRowArray();

                $candidates = [
                    'guru' => trim((string) ($row['guru_nama'] ?? '')),
                    'pegawai' => trim((string) ($row['pegawai_nama'] ?? '')),
                    'siswa' => trim((string) ($row['siswa_nama'] ?? '')),
                ];

                foreach ($candidates as $candidateSource => $candidateName) {
                    if ($candidateName === '') {
                        continue;
                    }

                    $displayName = $candidateName;
                    $source = $candidateSource;
                    break;
                }
            } catch (Throwable $e) {
                log_message(
                    'warning',
                    'Layout gagal me-resolve display identity user #{userId}: {message}',
                    [
                        'userId' => $userId,
                        'message' => $e->getMessage(),
                    ]
                );
            }
        }

        $firstCharacter = function_exists('mb_substr')
            ? mb_substr($displayName, 0, 1, 'UTF-8')
            : substr($displayName, 0, 1);

        $initial = function_exists('mb_strtoupper')
            ? mb_strtoupper($firstCharacter, 'UTF-8')
            : strtoupper($firstCharacter);

        return [
            'name' => $displayName,
            'initial' => $initial !== '' ? $initial : '?',
            'source' => $source,
        ];
    }

    private function loadSystemSettings(): array
    {
        $defaults = [
            'nama_sekolah' => 'MTsN 4 Jombang',
            'alamat_sekolah' => '',
            'logo_sekolah' => '',
            'icon_sekolah' => '',
        ];

        try {
            $rows = (new SettingSistemModel())->allAssoc();

            foreach ($defaults as $key => $default) {
                if (! isset($rows[$key])) {
                    continue;
                }

                $value = trim((string) ($rows[$key]['setting_value'] ?? ''));

                if ($value !== '') {
                    $defaults[$key] = $value;
                }
            }
        } catch (Throwable $e) {
            log_message(
                'warning',
                'Layout gagal memuat setting_sistem: {message}',
                ['message' => $e->getMessage()]
            );
        }

        return $defaults;
    }
}
