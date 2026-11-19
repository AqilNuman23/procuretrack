<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class FormSubmissionResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('POST')
            && $request->header('X-ProcureTrack-Form') === '1'
            && $response instanceof RedirectResponse) {
            // Leave the flash data for the browser's next navigation and preserve response cookies.
            $body = json_encode(['redirect' => $response->getTargetUrl()], JSON_THROW_ON_ERROR);
            $response->setStatusCode(Response::HTTP_OK);
            $response->headers->remove('Location');
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent($body);
        }

        return $response;
    }
}
