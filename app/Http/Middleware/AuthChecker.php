<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;
class AuthChecker
{
    private $publicRoutes = [
        'home',
        'login',
        'loginCallback',
        'logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!Auth::check() && !in_array($request->route()->getName(), $this->publicRoutes))
            return redirect('/');

        return $next($request);
    }
}