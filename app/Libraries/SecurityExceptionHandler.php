<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Renders SecurityException (403 — e.g. an expired/invalid CSRF token on a
 * preview POST) as a plain "Artikel tidak tersedia" page with NO exception
 * details and NO stack trace, for both HTML and non-HTML requests. The HTTP
 * status stays 403; CSRF validation itself is untouched (the standard CSRF
 * filter still runs and still rejects the request — this only changes how
 * the rejection is presented).
 */
class SecurityExceptionHandler implements ExceptionHandlerInterface
{
    public function __construct(protected \Config\Exceptions $config)
    {
    }

    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        if ($request instanceof CLIRequest || (function_exists('is_cli') && is_cli())) {
            CLI::write('Artikel tidak tersedia');
            if (ENVIRONMENT !== 'testing') {
                exit($exitCode);
            }

            return;
        }

        $isHtml = str_contains($request->getHeaderLine('accept'), 'text/html');

        if ($isHtml) {
            $response->setStatusCode($statusCode);
            $response->setBody(view('errors/html/error_403'));
        } else {
            $response->setStatusCode($statusCode);
            $response->setJSON(['error' => 'Artikel tidak tersedia']);
        }

        $response->send();

        if (ENVIRONMENT !== 'testing') {
            // @codeCoverageIgnoreStart
            exit($exitCode);
            // @codeCoverageIgnoreEnd
        }
    }
}
