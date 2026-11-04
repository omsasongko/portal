<?php

namespace App\Libraries;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Debug\ExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Intercepts database connection failures and returns a clean HTTP 503 page
 * in Indonesian. No stack traces, SQL, credentials, or filesystem paths are
 * exposed.
 *
 * Implements ExceptionHandlerInterface (ExceptionHandler is final and cannot
 * be extended). Non-connection exceptions are delegated to the framework's
 * default handler so all other error handling is preserved.
 */
class DatabaseExceptionHandler implements ExceptionHandlerInterface
{
    protected BaseConfig $config;

    public function __construct(BaseConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param \CodeIgniter\HTTP\CLIRequest|IncomingRequest $request
     */
    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        // Log diagnostic details server-side without credentials.
        $uri = ($request instanceof IncomingRequest)
            ? $request->getMethod() . ' ' . ($request->getPath() ?: '/')
            : 'CLI';

        log_message('critical', 'Database connection failure [{route}]: {msg}', [
            'route' => $uri,
            'msg'   => $this->redactMessage($exception->getMessage()),
        ]);

        if ($request instanceof IncomingRequest) {
            $response->setStatusCode(503, 'Service Unavailable');
            $response->setHeader('Content-Type', 'text/html; charset=UTF-8');
            $response->setHeader('Cache-Control', 'no-store, max-age=0');
            $response->setBody($this->render503Page());

            if (! headers_sent()) {
                header(
                    sprintf(
                        'HTTP/%s 503 Service Unavailable',
                        $request->getProtocolVersion(),
                    ),
                    true,
                    503,
                );
            }

            $response->send();
        } else {
            fwrite(STDERR, "Maaf, database tidak terhubung. Silakan coba beberapa saat lagi.\n");
        }

        if (ENVIRONMENT !== 'testing') {
            exit($exitCode);
        }
    }

    /**
     * Strip credential-like fragments before they reach the log file.
     */
    protected function redactMessage(string $message): string
    {
        $message = preg_replace('/\buser(?:name)?\s*[=:]\s*\S+/i', 'user=***', $message) ?? $message;
        $message = preg_replace('/\bpassw(?:or)?d\s*[=:]\s*\S+/i', 'password=***', $message) ?? $message;

        return $message;
    }

    /**
     * Self-contained 503 page. No database, no framework views, no config
     * values — always renders successfully.
     */
    protected function render503Page(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Database Tidak Terhubung</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f6f9;
            color: #212529;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .box {
            background: #fff;
            border-radius: 10px;
            padding: 48px 36px;
            max-width: 460px;
            width: 100%;
            text-align: center;
            box-shadow: 0 2px 16px rgba(0,0,0,.07);
        }
        .icon {
            width: 64px; height: 64px;
            margin: 0 auto 20px;
            background: #e8f4fd;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .icon svg { width: 32px; height: 32px; stroke: #0d6efd; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        h1 { font-size: 22px; font-weight: 600; margin-bottom: 12px; }
        p  { font-size: 16px; line-height: 1.6; color: #6c757d; margin-bottom: 28px; }
        .btn {
            display: inline-block;
            background: #0d6efd; color: #fff;
            font-size: 15px; font-weight: 600;
            padding: 12px 32px; border-radius: 6px;
            border: none; cursor: pointer;
            text-decoration: none;
        }
        .btn:hover { background: #0b5ed7; }
        .code { margin-top: 24px; font-size: 13px; color: #adb5bd; }
        @media(max-width:480px){ .box{ padding:36px 20px; } h1{ font-size:19px; } }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">
            <svg viewBox="0 0 24 24">
                <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
            </svg>
        </div>
        <h1>Maaf, database tidak terhubung.</h1>
        <p>Silakan coba beberapa saat lagi.</p>
        <button type="button" class="btn" onclick="window.location.reload()">Coba Lagi</button>
        <div class="code">HTTP 503</div>
    </div>
</body>
</html>
HTML;
    }
}
