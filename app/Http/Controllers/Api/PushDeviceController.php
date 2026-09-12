<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushDeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:1024'],
            'platform' => ['required', 'string', Rule::in(['android', 'web', 'ios'])],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $device = FcmDevice::query()->updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'],
                'device_name' => $data['device_name'] ?? null,
                'last_seen_at' => now(),
            ],
        );

        return response()->json([
            'message' => 'Dispositivo registrado para notificaciones.',
            'id' => $device->id,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:1024'],
        ]);

        FcmDevice::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $data['token'])
            ->delete();

        return response()->json(['message' => 'Dispositivo eliminado.']);
    }
}
