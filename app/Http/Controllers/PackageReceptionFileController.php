<?php

namespace App\Http\Controllers;

use App\Models\PackageReceptionFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PackageReceptionFileController extends Controller
{
    public function __invoke(PackageReceptionFile $file)
    {
        Gate::authorize('view', $file->packageReception);
        abort_unless(Storage::disk('local')->exists($file->path), 404);
        return Storage::disk('local')->download($file->path, $file->original_name ?: basename($file->path));
    }
}
