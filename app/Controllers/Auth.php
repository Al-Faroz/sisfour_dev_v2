<?php

namespace App\Controllers;

use App\Services\AuthService;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Auth — halaman login/logout (web). Bukan turunan BaseController karena
 * halaman login belum punya session (tidak butuh sidebar).
 */
class Auth extends Controller
{
    protected AuthService $authService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->authService = new AuthService();
    }

    public function login()
    {
        // Kalau sudah login, jangan tampilkan form login lagi — langsung ke dashboard.
        if (session()->get('logged_in')) {
            return redirect()->to('/dashboard');
        }

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $username = trim((string) $this->request->getPost('username'));
            $password = (string) $this->request->getPost('password');

            $result = $this->authService->attemptLogin($username, $password);

            if (!$result['success']) {
                session()->setFlashdata('error', $result['message']);
                return redirect()->back()->withInput();
            }

            $this->authService->setUserSession($result['user']);

            // INI BAGIAN YANG SEBELUMNYA HILANG / TIDAK LENGKAP:
            // pastikan redirect benar-benar terjadi ke /dashboard, bukan render ulang login.
            return redirect()->to('/dashboard');
        }

        return view('auth_login');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/auth/login');
    }
}
