<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ValidateMemberRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Register member baru (status: menunggu validasi).
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Pendaftaran berhasil! Akun Anda menunggu validasi dari Owner.',
            'data' => new UserResource($user->load('roles')),
        ], 201);
    }

    /**
     * Login dan dapatkan token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'message' => 'Login berhasil.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ]);
    }

    /**
     * Logout (hapus token).
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Lihat profil user yang sedang login.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource(
                $request->user()->load('roles', 'membership', 'leaderboard')
            ),
        ]);
    }

    /**
     * Owner: Lihat daftar member yang menunggu validasi.
     */
    public function pendingMembers(): JsonResponse
    {
        $members = User::where('validation_status', 'menunggu')
            ->with('roles')
            ->latest()
            ->get();

        return response()->json([
            'data' => UserResource::collection($members),
        ]);
    }

    /**
     * Owner: Approve atau reject member.
     */
    public function validateMember(ValidateMemberRequest $request, User $user): JsonResponse
    {
        if ($user->validation_status !== 'menunggu') {
            return response()->json([
                'message' => 'Member ini sudah divalidasi sebelumnya.',
            ], 422);
        }

        if ($request->action === 'approve') {
            $user = $this->authService->approveMember($user);
            $message = 'Member berhasil disetujui. Member ID: ' . $user->member_id;
        } else {
            $user = $this->authService->rejectMember($user, $request->reason);
            $message = 'Member ditolak. Alasan: ' . $request->reason;
        }

        return response()->json([
            'message' => $message,
            'data' => new UserResource($user->load('roles')),
        ]);
    }
}
