<?php

namespace Modules\Login\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FcmService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\Login\Mail\OtpMail;
use Modules\Notification\Models\Notification;
use Modules\PgManagement\Models\PgManagement;
use Modules\Subscription\Models\Subscription;
use Modules\User\Models\User;

class AuthController extends Controller
{
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email.',
            ], 404);
        }

        if ($user->status !== 'Active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Contact admin.',
            ], 403);
        }

        if ($user->is_blocked) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is blocked. Contact admin.',
            ], 403);
        }

        if (! $user->hasRole('Pg_Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only Pg_Admin users can log in via the app.',
            ], 403);
        }

        $hasActiveSubscription = Subscription::where('email', $email)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists();

        if (! $hasActiveSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'Your subscription has expired. Please renew to continue.',
            ], 403);
        }

        // Clean old OTPs for this email
        DB::table('otps')->whereRaw('LOWER(email) = ?', [$email])->delete();

        // Generate 6-digit OTP
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('otps')->insert([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(10),
            'used' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            Mail::to($email)->send(new OtpMail($otp, $user->name));
        } catch (\Throwable $e) {
            DB::table('otps')->where('email', $email)->where('otp', $otp)->delete();
            logger()->error('OTP email failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'OTP could not be sent. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email.',
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $otp = trim((string) $request->input('otp'));

        $record = DB::table('otps')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('otp', $otp)
            ->where('expires_at', '>=', Carbon::now())
            ->orderByDesc('id')
            ->first();

        if (! $record) {
            logger()->warning('OTP verify mismatch', [
                'email' => $email,
                'entered_otp' => $otp,
                'record_found' => (bool) $record,
                'record_otp' => $record->otp ?? null,
                'record_expires_at' => $record->expires_at ?? null,
                'record_used' => $record->used ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        // Mark OTP as used on first successful verification.
        if (! (bool) $record->used) {
            DB::table('otps')->where('id', $record->id)->update(['used' => true]);
        }

        $user = User::where('email', $email)->first();

        $hasActiveSubscription = Subscription::where('email', $email)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists();

        if (! $hasActiveSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'Your subscription has expired. Please renew to continue.',
            ], 403);
        }

        // Persist device info
        $user->fcm_token = $request->input('fcm_token');
        $user->device_name = $request->input('device_name');
        $user->save();

        // Revoke old tokens
        $user->tokens()->delete();

        // Create new Sanctum token
        $token = $user->createToken('mobile-app')->plainTextToken;

        // Send login notification
        try {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'login',
                'title' => 'Login Successful',
                'body' => 'You have logged in successfully.',
            ]);
            app(FcmService::class)->sendToUser($user, 'Login Successful', 'You have logged in successfully.', ['type' => 'login']);
        } catch (\Throwable $e) {
            logger()->error('Login notification failed: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'id' => (string) $user->id,
                'public_id' => (string) ($user->public_id ?: $user->id),
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'token' => $token,
                'current_pg' => $user->current_pg ? (string) $user->current_pg : null,
                'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
                'roles' => $user->getRoleNames()->values()->all(),
            ],
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email.',
            ], 404);
        }

        if ($user->status !== 'Active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Contact admin.',
            ], 403);
        }

        if ($user->is_blocked) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is blocked. Contact admin.',
            ], 403);
        }

        if (! $user->hasRole('Pg_Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only Pg_Admin users can log in via the app.',
            ], 403);
        }

        $hasActiveSubscription = Subscription::where('email', $email)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists();

        if (! $hasActiveSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'Your subscription has expired. Please renew to continue.',
            ], 403);
        }

        if (! Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password.',
            ], 401);
        }

        // Persist device info
        $user->fcm_token = $request->input('fcm_token');
        $user->device_name = $request->input('device_name');
        $user->save();

        // Revoke old tokens
        $user->tokens()->delete();

        // Create new Sanctum token
        $token = $user->createToken('mobile-app')->plainTextToken;

        // Send login notification
        try {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'login',
                'title' => 'Login Successful',
                'body' => 'You have logged in successfully.',
            ]);
            app(FcmService::class)->sendToUser($user, 'Login Successful', 'You have logged in successfully.', ['type' => 'login']);
        } catch (\Throwable $e) {
            logger()->error('Login notification failed: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'id' => (string) $user->id,
                'public_id' => (string) ($user->public_id ?: $user->id),
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'token' => $token,
                'current_pg' => $user->current_pg ? (string) $user->current_pg : null,
                'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
                'roles' => $user->getRoleNames()->values()->all(),
            ],
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'User fetched successfully.',
            'data' => [
                'id' => (string) $user->id,
                'public_id' => (string) ($user->public_id ?: $user->id),
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'current_pg' => $user->current_pg ? (string) $user->current_pg : null,
                'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
                'roles' => $user->getRoleNames()->values()->all(),
            ],
        ]);
    }

    public function updateCurrentPg(Request $request): JsonResponse
    {
        $request->validate([
            'pg_id' => 'required|integer|exists:pg_management,id',
        ]);

        $user = auth()->user();
        $pgId = $request->integer('pg_id');

        if ($user->hasRole('Pg_Admin')) {
            $pg = PgManagement::where('id', $pgId)->where('owner_id', $user->id)->exists();
            if (! $pg) {
                return response()->json([
                    'success' => false,
                    'message' => 'PG not found or access denied.',
                ], 403);
            }
        }

        $user->current_pg = $pgId;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Current PG updated.',
        ]);
    }

    public function updateDeviceToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = auth()->user();
        $user->fcm_token = $request->input('fcm_token');
        $user->device_name = $request->input('device_name');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Device token updated.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Revoke the current Sanctum token
        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
