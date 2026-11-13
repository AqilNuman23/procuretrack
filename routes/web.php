<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'app');

// Local-only, fictional UI previews. No authentication or business actions run here.
if (app()->environment(['local', 'testing'])) {
    Route::get('/design/{page?}', function (string $page = 'hub') {
        $design = config('design');
        abort_unless(isset($design['pages'][$page]), 404);

        return view('design.page', [
            'design' => $design,
            'screen' => $design['pages'][$page],
            'pageKey' => $page,
        ]);
    })->name('design');
}
