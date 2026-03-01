<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberValidationResource\Pages;
use App\Models\Membership;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class MemberValidationResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getNavigationIcon(): string|null
    {
        return 'heroicon-o-user-plus';
    }

    public static function getNavigationLabel(): string
    {
        return 'Validasi Member';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'User & Membership';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    /**
     * Badge merah menampilkan jumlah akun yang menunggu validasi.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = User::where('validation_status', 'menunggu')
            ->whereHas('roles', fn ($q) => $q->where('name', 'Member'))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    /**
     * Hanya Owner yang bisa akses validasi member.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Owner') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->whereHas('roles', fn ($q) => $q->where('name', 'Member'))
                    ->with(['roles'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('No HP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(40)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('member_id')
                    ->label('Member ID')
                    ->placeholder('Belum ada'),
                Tables\Columns\TextColumn::make('validation_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aktif'     => 'success',
                        'menunggu'  => 'warning',
                        'ditolak'   => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'aktif'     => 'Aktif',
                        'menunggu'  => 'Menunggu Validasi',
                        'ditolak'   => 'Ditolak',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tgl Daftar')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('validation_status')
                    ->label('Filter Status')
                    ->options([
                        'menunggu' => 'Menunggu Validasi',
                        'aktif'    => 'Aktif',
                        'ditolak'  => 'Ditolak',
                    ])
                    ->default('menunggu'),
            ])
            ->actions([
                // Approve
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Akun Member?')
                    ->modalDescription(fn (User $record): string => "Akun \"{$record->name}\" akan diaktifkan dan mendapat Member ID.")
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->visible(fn (User $record): bool => $record->validation_status === 'menunggu')
                    ->action(function (User $record) {
                        // Generate Member ID unik
                        $lastMember = User::whereNotNull('member_id')
                            ->orderByDesc('member_id')->first();
                        $nextNum = $lastMember
                            ? ((int) ltrim(str_replace('MBR-', '', $lastMember->member_id), '0') + 1)
                            : 1;
                        $memberId = 'MBR-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

                        $record->update([
                            'validation_status' => 'aktif',
                            'member_id'         => $memberId,
                        ]);

                        // Buat entry membership awal (tier REGULAR, poin 0)
                        Membership::firstOrCreate(
                            ['user_id' => $record->id],
                            ['tier' => 'regular', 'total_points' => 0]
                        );

                        Notification::make()
                            ->title('Akun Disetujui')
                            ->body("{$record->name} telah diaktifkan dengan {$memberId}.")
                            ->success()
                            ->send();
                    }),

                // Reject
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan (opsional)')
                            ->placeholder('Contoh: Data tidak lengkap, nomor HP tidak valid...')
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Akun Member?')
                    ->modalDescription(fn (User $record): string => "Akun \"{$record->name}\" akan ditolak.")
                    ->modalSubmitActionLabel('Ya, Tolak')
                    ->visible(fn (User $record): bool => $record->validation_status === 'menunggu')
                    ->action(function (User $record, array $data) {
                        $record->update([
                            'validation_status' => 'ditolak',
                        ]);

                        Notification::make()
                            ->title('Akun Ditolak')
                            ->body("{$record->name} telah ditolak." . ($data['rejection_reason'] ? " Alasan: {$data['rejection_reason']}" : ''))
                            ->warning()
                            ->send();
                    }),

                // Reaktivasi (untuk yang sudah ditolak)
                Tables\Actions\Action::make('reactivate')
                    ->label('Aktifkan Ulang')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Aktifkan Ulang?')
                    ->modalSubmitActionLabel('Ya, Aktifkan')
                    ->visible(fn (User $record): bool => $record->validation_status === 'ditolak')
                    ->action(function (User $record) {
                        $record->update(['validation_status' => 'menunggu']);

                        Notification::make()
                            ->title('Status diubah ke Menunggu')
                            ->body("{$record->name} dikembalikan ke status menunggu validasi.")
                            ->info()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemberValidations::route('/'),
        ];
    }
}
