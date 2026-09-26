<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function download(Document $document)
    {
        Gate::authorize('download', $document);

        abort_unless(
            Storage::disk('local')->exists($document->path),
            404
        );

        return Storage::disk('local')->download(
            $document->path,
            $document->name
        );
    }
}