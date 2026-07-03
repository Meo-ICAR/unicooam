<?php

use App\Models\Document;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

Route::redirect('/', '/admin');

Route::get('/documents/{document}/download', function (Document $document): BinaryFileResponse {
    $media = $document->getFirstMedia('documents');

    if (! $media) {
        abort(404);
    }

    return response()->download($media->getPath(), $media->file_name);
})->name('documents.download');

/*
 * Route::get('/', function () {
 *     return view('welcome');
 * });
 */
