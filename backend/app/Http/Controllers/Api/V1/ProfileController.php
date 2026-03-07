<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success($user->fresh(), 'Profile retrieved successfully');
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->safe()->except(['avatar', 'remove_avatar']);

        if ($request->boolean('remove_avatar') && is_string($user->avatar_path) && $user->avatar_path !== '') {
            Storage::disk('public')->delete($user->avatar_path);
            $validated['avatar_path'] = null;
        }

        if ($request->hasFile('avatar')) {
            if (is_string($user->avatar_path) && $user->avatar_path !== '') {
                Storage::disk('public')->delete($user->avatar_path);
            }

            /** @var UploadedFile $avatar */
            $avatar = $request->file('avatar');
            $extension = $avatar->getClientOriginalExtension() ?: $avatar->extension() ?: 'bin';
            $validated['avatar_path'] = $avatar->storeAs(
                'avatars',
                $user->id.'.'.$extension,
                'public'
            );
        }

        $user->update($validated);

        return $this->success($user->fresh(), 'Profile updated successfully');
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->update([
            'password' => $request->string('password')->toString(),
        ]);

        /** @var PersonalAccessToken|null $currentToken */
        $currentToken = $user->currentAccessToken();

        $query = $user->tokens();

        if ($currentToken instanceof PersonalAccessToken) {
            $query->whereKeyNot($currentToken->getKey());
        }

        $query->delete();

        return $this->success($user->fresh(), 'Password updated successfully');
    }
}
