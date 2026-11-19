<?php

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DisplayPreferencesController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate(['theme' => ['sometimes', 'required', 'in:dark,light'], 'locale' => ['sometimes', 'required', 'in:en,ms'],
            'return_to' => ['required', 'string', 'max:2048', 'regex:~^/(?!/)[^\r\n\\\\]*$~']]);
        $response = redirect($data['return_to']);
        foreach (['theme', 'locale'] as $key) {
            if (isset($data[$key])) {
                $response->withCookie(cookie($key, $data[$key], 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
            }
        }

        return $response;
    }
}
