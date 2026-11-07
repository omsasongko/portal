<?php

namespace App\Controllers;

use App\Models\ContactMessageModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Handles public contact form submissions.
 *
 * Submissions are saved for admin review only — no email or
 * WhatsApp messages are sent automatically.
 */
class Contact extends BaseController
{
    /** Maximum submissions per IP within the rate window. */
    private const RATE_LIMIT     = 5;
    private const RATE_WINDOW_MIN = 15;

    /**
     * Helper: add a fresh CSRF token/hash to an array for JSON responses.
     */
    private function withCsrf(array $data): array
    {
        $data['csrf'] = [
            'name'  => csrf_token(),
            'value' => csrf_hash(),
        ];
        return $data;
    }

    /**
     * Process a contact form submission (AJAX POST).
     */
    public function submit(): ResponseInterface
    {
        // Only accept POST requests.
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setStatusCode(405);
        }

        // Honeypot check: if the hidden "website" field has a value, reject silently.
        $honeypot = $this->request->getPost('website');
        if ($honeypot !== null && $honeypot !== '') {
            return $this->response->setJSON($this->withCsrf([
                'ok'      => true,
                'message' => 'Pesan Anda telah terkirim. Terima kasih!',
            ]));
        }

        // Server-side validation.
        $rules = [
            'name'    => 'required|max_length[200]',
            'email'   => 'required|valid_email|max_length[200]',
            'phone'   => 'permit_empty|max_length[50]',
            'message' => 'required|max_length[5000]',
        ];
        $messages = [
            'name' => [
                'required'   => 'Nama wajib diisi.',
                'max_length' => 'Nama maksimal 200 karakter.',
            ],
            'email' => [
                'required'    => 'Email wajib diisi.',
                'valid_email' => 'Format email tidak valid.',
                'max_length'  => 'Email maksimal 200 karakter.',
            ],
            'phone' => [
                'max_length' => 'Telepon/WhatsApp maksimal 50 karakter.',
            ],
            'message' => [
                'required'   => 'Pesan wajib diisi.',
                'max_length' => 'Pesan maksimal 5.000 karakter.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return $this->response->setStatusCode(422)->setJSON($this->withCsrf([
                'ok'     => false,
                'errors' => $this->validator->getErrors(),
            ]));
        }

        $ip = $this->request->getIPAddress();
        $model = new ContactMessageModel();

        // Rate limiting: max N submissions per IP within the last M minutes.
        // Loopback IPs (127.0.0.1, ::1) are exempt because they only appear
        // during local development.  CI4's getIPAddress() returns the real
        // TCP peer address (proxyIPs is empty), so external visitors can
        // never present a loopback IP in production.
        if (! in_array($ip, ['127.0.0.1', '::1'], true)) {
            $cutoff = date('Y-m-d H:i:s', strtotime('-' . self::RATE_WINDOW_MIN . ' minutes'));
            $recent = $model->where('ip_address', $ip)
                            ->where('created_at >=', $cutoff)
                            ->countAllResults(false);

            if ($recent >= self::RATE_LIMIT) {
                return $this->response->setStatusCode(429)->setJSON($this->withCsrf([
                    'ok'      => false,
                    'message' => 'Terlalu banyak pesan dalam waktu singkat. Silakan coba lagi nanti.',
                ]));
            }
        }

        // Duplicate detection: same name+email+message within 2 minutes.
        $name    = trim($this->request->getPost('name'));
        $email   = trim($this->request->getPost('email'));
        $phone   = trim($this->request->getPost('phone') ?? '');
        $message = trim($this->request->getPost('message'));

        $dupCutoff = date('Y-m-d H:i:s', strtotime('-2 minutes'));
        $dup = $model->where('name', $name)
                     ->where('email', $email)
                     ->where('message', $message)
                     ->where('created_at >=', $dupCutoff)
                     ->countAllResults(false);

        if ($dup > 0) {
            return $this->response->setJSON($this->withCsrf([
                'ok'      => true,
                'message' => 'Pesan Anda telah terkirim. Terima kasih!',
            ]));
        }

        $model->insert([
            'name'       => $name,
            'email'      => $email,
            'phone'      => $phone !== '' ? $phone : null,
            'message'    => $message,
            'ip_address' => $ip,
        ]);

        return $this->response->setJSON($this->withCsrf([
            'ok'      => true,
            'message' => 'Pesan Anda telah terkirim. Terima kasih!',
        ]));
    }
}
