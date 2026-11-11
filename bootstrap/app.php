<?php

require_once __DIR__ . '/helpers.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware aliases
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'superadmin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'role.access' => \App\Http\Middleware\RoleAccessMiddleware::class,
            '2fa' => \App\Http\Middleware\EnsureTwoFactorIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // * Return JSON for AJAX/JSON requests instead of HTML error pages
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // Check multiple conditions to detect AJAX/JSON requests
            $isAjax = $request->expectsJson() 
                   || $request->ajax() 
                   || $request->wantsJson()
                   || $request->header('X-Requested-With') === 'XMLHttpRequest'
                   || str_contains($request->header('Accept', ''), 'application/json');
            
            if ($isAjax) {
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                
                // * Handle 403 Forbidden errors specifically
                if ($statusCode === 403 || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() === 403) {
                    \Log::warning('403 Forbidden Error for AJAX Request', [
                        'user_id' => auth()->id(),
                        'user_role' => auth()->user() ? auth()->user()->role : 'not authenticated',
                        'route' => $request->route() ? $request->route()->getName() : 'unknown',
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                        'message' => $e->getMessage(),
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage() ?: 'You do not have permission to perform this action. Please ensure you have the correct role and branch access.'
                    ], 403)->header('Content-Type', 'application/json');
                }
                
                // Handle specific exception types
                if ($e instanceof \Illuminate\Database\QueryException) {
                    $errorDetails = [
                        'user_id' => auth()->id(),
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'sql' => method_exists($e, 'getSql') ? $e->getSql() : 'N/A',
                        'bindings' => method_exists($e, 'getBindings') ? $e->getBindings() : [],
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ];
                    
                    \Log::error('Database Query Error in Exception Handler: ' . $e->getMessage(), $errorDetails);
                    error_log('EXCEPTION_HANDLER_QUERY_ERROR: ' . json_encode($errorDetails));
                    
                    // Return error response
                    $response = [
                        'success' => false,
                        'message' => 'Database error occurred. Please try again.',
                    ];
                    
                    // Only include debug info if APP_DEBUG is enabled
                    if (config('app.debug')) {
                        $response['debug'] = [
                            'error_type' => 'QueryException',
                            'error_message' => $e->getMessage(),
                            'error_code' => $e->getCode(),
                            'file' => basename($e->getFile()),
                            'line' => $e->getLine(),
                        ];
                    }
                    
                    return response()->json($response, 500)->header('Content-Type', 'application/json');
                }
                
                // Don't expose sensitive information in production
                $message = config('app.debug') ? $e->getMessage() : 'An error occurred processing your request.';
                
                // Log the actual error for debugging
                $errorDetails = [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'user_id' => auth()->id(),
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'trace' => $e->getTraceAsString()
                ];
                
                \Log::error('API Error in Exception Handler: ' . $e->getMessage(), $errorDetails);
                error_log('EXCEPTION_HANDLER_ERROR: ' . json_encode($errorDetails));
                
                // Return error response
                $response = [
                    'success' => false,
                    'message' => $message,
                ];
                
                // Only include debug info if APP_DEBUG is enabled
                if (config('app.debug')) {
                    $response['debug'] = [
                        'error_type' => get_class($e),
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'file' => basename($e->getFile()),
                        'line' => $e->getLine(),
                        'trace_preview' => substr($e->getTraceAsString(), 0, 500),
                    ];
                }
                
                return response()->json($response, $statusCode)->header('Content-Type', 'application/json');
            }
            
            // For regular web requests, use default HTML error page
            return null;
        });
    })->create();
