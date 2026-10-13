<?php

namespace App\Http\Middleware;

use App\Support\FormBlocklist;
use App\Support\FormGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global in the web group: a POST whose name, praxis, e-mail or phone is on
 * config('contact.blocklist') is dropped silently. The visitor is sent to
 * the same thank-you page as a real submission, so a bot learns nothing;
 * no controller runs, so no mail, CAS entry or other job is triggered.
 */
class BlockListedSubmissions
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || $request->is('webhooks/*')) {
            return $next($request);
        }

        $hit = FormBlocklist::findMatch($request->all(), (array) config('contact.blocklist', []));

        if ($hit === null) {
            return $next($request);
        }

        FormGuard::logRejection('blocklist', $hit['field'], ['liste' => $hit['list']]);

        return $this->redirectToThanks($request);
    }

    private function redirectToThanks(Request $request): Response
    {
        $routeName = $request->route()?->getName();
        $target = $routeName !== null ? config("contact.blocklist_redirects.{$routeName}") : null;
        $targetRoute = $target !== null ? app('router')->getRoutes()->getByName($target) : null;

        if ($targetRoute === null) {
            return back();
        }

        $parameters = array_intersect_key($request->route()->parameters(), array_flip($targetRoute->parameterNames()));

        return redirect()->route($target, $parameters);
    }
}
