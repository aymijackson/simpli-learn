<?php

namespace App\Http\Controllers;

use App\Support\Tenancy\Tenancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'mp4', 'webm',
        'mp3', 'wav', 'ogg',
    ];

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:'.implode(',', self::ALLOWED_EXTENSIONS)],
        ]);

        $file = $request->file('file');
        $tenantId = app(Tenancy::class)->id();
        $directory = 'uploads/'.($tenantId ?? 'central');
        $filename = Str::random(40).'.'.$file->getClientOriginalExtension();

        $path = $file->storeAs($directory, $filename, 'public');

        return response()->json(['url' => Storage::disk('public')->url($path)]);
    }
}
