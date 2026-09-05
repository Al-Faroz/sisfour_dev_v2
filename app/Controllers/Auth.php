<?php

namespace App\Controllers;

use App\Services\AuthService;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Auth extends Controller
{
    protected AuthService $authService;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->authService = new AuthService();
    }

    /**
     * Login web.
     *
     * GET  /auth/login -> tampilkan form
     * POST /auth/login -> proses login
     */
    public function login()
    {
        // Jika sudah login, langsung ke dashboard.
        if (session()->get('logged_in') === true) {
            return redirect()->to('/dashboard');
        }

        // GET -> tampilkan halaman login.
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return view('auth_login');
        }

        // POST -> proses login.
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        // Simpan username saja untuk ditampilkan kembali.
        // PASSWORD TIDAK BOLEH disimpan sebagai old input/session.
        if ($username !== '') {
            session()->setFlashdata('login_username', $username);
        }

        $result = $this->authService->attemptLogin($username, $password);

        if (!$result['success']) {
            session()->setFlashdata('error', $result['message']);

            // Jangan gunakan withInput().
            // Dengan password field, withInput() dapat menyebabkan
            // password masuk ke old input/session.
            return redirect()->to('/auth/login');
        }

        /*
         * Regenerasi session setelah autentikasi berhasil.
         *
         * destroy=true:
         * - membuang session ID lama
         * - membersihkan data session lama
         * - mencegah session fixation
         *
         * Setelah itu setUserSession() mengisi session baru.
         */
        session()->regenerate(true);

        $this->authService->setUserSession($result['user']);

        return redirect()->to('/dashboard');
    }

    /**
     * Logout web.
     *
     * Hanya POST.
     */
    public function logout()
    {
        session()->destroy();

        return redirect()->to('/auth/login');
    }
}
