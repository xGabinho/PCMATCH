<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class ErrorLoggerService
{
    /**
     * Canal por defecto para registro de errores estructurados.
     */
    protected static string $channel = 'errors';

    /**
     * Captura datos contextuales de la petición HTTP actual si existe.
     *
     * @return array
     */
    protected static function getRequestContext(): array
    {
        $context = [];
        try {
            if (function_exists('request') && request()) {
                $req = request();
                $context['url'] = $req->fullUrl();
                $context['method'] = $req->method();
                $context['ip'] = $req->ip();
                $context['user_agent'] = $req->userAgent();
                if ($req->user()) {
                    $context['user_id'] = $req->user()->id ?? null;
                    $context['user_email'] = $req->user()->email ?? null;
                }
            }
        } catch (Throwable $e) {
            // Ignorar errores al acceder al request context
        }
        return $context;
    }

    /**
     * Registra una excepción con todos sus detalles estructurados (file, line, trace, context).
     *
     * @param Throwable $exception
     * @param string $level (debug, info, notice, warning, error, critical, alert, emergency)
     * @param array $extraContext
     * @return void
     */
    public static function logException(Throwable $exception, string $level = 'error', array $extraContext = []): void
    {
        $trace = $exception->getTrace();
        $caller = $trace[0] ?? [];

        $context = array_merge(
            [
                'timestamp'   => now()->toIso8601String(),
                'file'        => $exception->getFile(),
                'line'        => $exception->getLine(),
                'class'       => $caller['class'] ?? 'N/A',
                'function'    => $caller['function'] ?? 'N/A',
                'code'        => $exception->getCode(),
                'stack_trace' => $exception->getTraceAsString(),
            ],
            self::getRequestContext(),
            $extraContext
        );

        $message = sprintf(
            '[%s] %s in %s:%d',
            strtoupper($level),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        Log::channel(self::$channel)->log($level, $message, $context);
    }

    /**
     * Registra un mensaje de log estructurado.
     *
     * @param string $message
     * @param string $level
     * @param array $context
     * @return void
     */
    public static function logMessage(string $message, string $level = 'error', array $context = []): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $backtrace[1] ?? $backtrace[0] ?? [];

        $logData = array_merge(
            [
                'timestamp' => now()->toIso8601String(),
                'file'      => $caller['file'] ?? 'unknown',
                'line'      => $caller['line'] ?? 0,
                'function'  => $caller['function'] ?? 'unknown',
                'class'     => $caller['class'] ?? 'N/A',
            ],
            self::getRequestContext(),
            $context
        );

        Log::channel(self::$channel)->log($level, $message, $logData);
    }

    public static function logInfo(string $message, array $context = []): void
    {
        self::logMessage($message, 'info', $context);
    }

    public static function logWarning(string $message, array $context = []): void
    {
        self::logMessage($message, 'warning', $context);
    }

    public static function logError(string $message, array $context = []): void
    {
        self::logMessage($message, 'error', $context);
    }

    public static function logCritical(string $message, array $context = []): void
    {
        self::logMessage($message, 'critical', $context);
    }
}
