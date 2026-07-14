<?php

namespace App\Http\Controllers;

use App\Services\FileAccessService;
use Illuminate\Http\Request;

class FileAccessController extends Controller
{
    public function __construct(
        private readonly FileAccessService $service,
    ) {}

    public function serve(Request $request, string $disk, string $path)
    {
        $result = $this->service->serve($disk, $path);

        if (isset($result['contents'])) {
            return response($result['contents'], 200, [
                'Content-Type' => $result['mime'],
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            ]);
        }

        abort($result['status'], $result['message']);
    }
}
