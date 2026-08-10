<?php

namespace Modules\User\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\User\Models\UserProfile;

class ProfileApiController extends Controller
{
    public function show(): JsonResponse
    {
        try {
            $user = Auth::user();
            $profile = $user->profile;

            return response()->json([
                'data' => [
                    'public_id' => (string) ($user->public_id ?: $user->id),
                    'name' => $user->name,
                    'firstname' => $profile?->firstname ?? '',
                    'lastname' => $profile?->lastname ?? '',
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'address' => $profile?->address ?? '',
                    'profile_photo' => $profile?->profile_photo
                        ? request()->getSchemeAndHttpHost().'/storage/'.$profile->profile_photo
                        : null,
                    'designation' => $user->designation ?? '',
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,'.Auth::id(),
            'mobile' => 'nullable|regex:/^[0-9]{10,15}$/',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $fullname = trim($request->firstname.' '.$request->lastname);
            $user->name = $fullname;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->save();

            $profile = UserProfile::firstOrNew(['user_id' => $user->id]);
            $profile->firstname = $request->firstname;
            $profile->lastname = $request->lastname ?? '';

            if ($request->hasFile('profile_photo')) {
                $profile->profile_photo = $request->file('profile_photo')->store('profile-photos', 'public');
            }

            $profile->save();

            DB::commit();

            return response()->json([
                'data' => [
                    'public_id' => (string) ($user->public_id ?: $user->id),
                    'name' => $user->name,
                    'firstname' => $profile->firstname,
                    'lastname' => $profile->lastname,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'address' => $profile->address ?? '',
                    'profile_photo' => $profile->profile_photo
                        ? request()->getSchemeAndHttpHost().'/storage/'.$profile->profile_photo
                        : null,
                    'designation' => $user->designation ?? '',
                ],
            ]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}
