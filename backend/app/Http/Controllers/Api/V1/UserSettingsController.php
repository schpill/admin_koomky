<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserSettings\UpdateProfileRequest;
use App\Http\Requests\UserSettings\UploadAvatarRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UserSettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        return $this->successResponse(data: [
            'type' => 'user',
            'id' => $user->id,
            'attributes' => $user->toArray(),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();

        $user->update($request->validated());

        return $this->successResponse(data: [
            'type' => 'user',
            'id' => $user->id,
            'attributes' => $user->fresh()->toArray(),
        ]);
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = auth()->user();

        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar_path' => $path]);
        }

        return $this->successResponse(data: [
            'type' => 'user',
            'id' => $user->id,
            'attributes' => $user->fresh()->toArray(),
        ]);
    }
}
