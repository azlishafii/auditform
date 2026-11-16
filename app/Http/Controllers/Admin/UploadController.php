<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
        ], [
            'image.image' => 'Fail mesti imej (JPG, PNG, WEBP).',
            'image.max' => 'Saiz imej maksimum 8MB.',
        ]);

        $path = $request->file('image')->store(date('Y/m'), 'uploads');

        return response()->json(['url' => '/uploads/'.$path]);
    }
}
