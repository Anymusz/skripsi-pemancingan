<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Membership;
use App\Models\Leaderboard;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register member baru (status: menunggu validasi).
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'address' => $data['address'] ?? null,
            'password' => Hash::make($data['password']),
            'validation_status' => 'menunggu',
        ]);

        $user->assignRole('Member');

        return $user;
    }

    /**
     * Login user dan return token.
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if ($user->validation_status === 'menunggu') {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda masih menunggu validasi dari Owner.'],
            ]);
        }

        if ($user->validation_status === 'ditolak') {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda telah ditolak. Silakan hubungi Owner.'],
            ]);
        }

        // Hapus token lama & buat token baru
        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user->load('roles', 'membership'),
            'token' => $token,
        ];
    }

    /**
     * Logout user (hapus token saat ini).
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Owner approve member: set aktif, generate member_id, buat membership & leaderboard.
     */
    public function approveMember(User $user): User
    {
        $user->update([
            'validation_status' => 'aktif',
            'member_id' => $this->generateMemberId(),
        ]);

        // Buat membership default (tier regular, 0 poin)
        Membership::firstOrCreate(
            ['user_id' => $user->id],
            ['tier' => 'regular', 'total_points' => 0]
        );

        // Buat entri leaderboard
        Leaderboard::firstOrCreate(
            ['user_id' => $user->id],
            ['total_fish_weight' => 0]
        );

        return $user->fresh(['roles', 'membership', 'leaderboard']);
    }

    /**
     * Owner reject member: set ditolak.
     */
    public function rejectMember(User $user, string $reason): User
    {
        $user->update([
            'validation_status' => 'ditolak',
        ]);

        return $user->fresh();
    }

    /**
     * Generate unique Member ID (format: MBR-XXXXX).
     */
    private function generateMemberId(): string
    {
        do {
            $memberId = 'MBR-' . strtoupper(Str::random(5));
        } while (User::where('member_id', $memberId)->exists());

        return $memberId;
    }
}
