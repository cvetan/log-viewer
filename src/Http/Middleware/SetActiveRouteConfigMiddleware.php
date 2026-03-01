<?php

namespace Opcodes\LogViewer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Opcodes\LogViewer\Facades\LogViewer;

class SetActiveRouteConfigMiddleware
{
    public function handle(Request $request, Closure $next, string $routeKey): mixed
    {
        $routeConfig = config("log-viewer.routes.{$routeKey}", []);

        if (isset($routeConfig['include_files'])) {
            config(['log-viewer.include_files' => $routeConfig['include_files']]);
        }

        if (isset($routeConfig['exclude_files'])) {
            config(['log-viewer.exclude_files' => $routeConfig['exclude_files']]);
        }

        if (isset($routeConfig['path'])) {
            config(['log-viewer.route_path' => $routeConfig['path']]);
        }

        // Clear cached files so the route-specific config takes effect
        LogViewer::clearFileCache();

        // Store the route key and name prefix for use in controllers (e.g. for signed download URLs)
        $request->attributes->set('log-viewer-route-key', $routeKey);
        $request->attributes->set('log-viewer-route-prefix', "log-viewer.{$routeKey}");

        return $next($request);
    }
}
