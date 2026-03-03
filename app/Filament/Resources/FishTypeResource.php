<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FishTypeResource\Pages;
use App\Models\FishType;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;

class FishTypeResource extends Resource
{
    protected static ?string $model = FishType::class;

    public static function getNavigationIcon(): string|null
    {
        return 'heroicon-o-scale';
    }

    public static function getNavigationLabel(): string
    {
        return 'Stok Ikan';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Inventaris';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationBadge(): ?string
    {
        if (!auth()->user()?->can('manage-fish-stock')) {
            return 'Dibatasi';
        }
        return null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        if (!auth()->user()?->can('manage-fish-stock')) {
            return 'danger';
        }
        return null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Ikan')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Ikan')
                            ->required(),
                        TextInput::make('price_per_kg')
                            ->label('Harga per Kg (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                        TextInput::make('stock_kg')
                            ->label('Stok Awal (Kg)')
                            ->helperText('Untuk menambah stok, gunakan tombol Restock di tabel.')
                            ->numeric()
                            ->suffix('kg')
                            ->required(),
                        TextInput::make('min_stock_threshold')
                            ->label('Batas Minimum Stok (Kg)')
                            ->helperText('Alert akan muncul jika stok di bawah angka ini.')
                            ->numeric()
                            ->suffix('kg')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Ikan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_per_kg')
                    ->label('Harga/Kg')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_kg')
                    ->label('Stok (Kg)')
                    ->numeric(2)
                    ->sortable()
                    ->color(fn (FishType $record): string => $record->isBelowThreshold() ? 'danger' : 'success')
                    ->weight(fn (FishType $record): string => $record->isBelowThreshold() ? 'bold' : 'normal'),
                Tables\Columns\TextColumn::make('min_stock_threshold')
                    ->label('Min. Stok')
                    ->numeric(2)
                    ->suffix(' kg'),
                Tables\Columns\IconColumn::make('stock_status')
                    ->label('Status')
                    ->state(fn (FishType $record): bool => !$record->isBelowThreshold())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn (FishType $record): string => $record->isBelowThreshold() ? 'Stok menipis!' : 'Stok aman'),
                Tables\Columns\TextColumn::make('last_restocked_at')
                    ->label('Terakhir Restock')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum pernah'),
            ])
            ->actions([
                Action::make('restock')
                    ->label('Restock')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('jumlah_restock')
                            ->label('Jumlah Restock (Kg)')
                            ->numeric()
                            ->suffix('kg')
                            ->required()
                            ->minValue(0.1),
                    ])
                    ->action(function (FishType $record, array $data) {
                        $tambahan = (float) $data['jumlah_restock'];
                        $stokBaru = (float)$record->stock_kg + $tambahan;

                        $record->update([
                            'stock_kg'          => $stokBaru,
                            'last_restocked_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Restock Berhasil!')
                            ->body("Stok {$record->name} bertambah {$tambahan} kg. Total: {$stokBaru} kg.")
                            ->success()
                            ->send();
                    })
                    ->modalHeading(fn (FishType $record): string => "Restock — {$record->name}")
                    ->modalDescription(fn (FishType $record): string => "Stok saat ini: {$record->stock_kg} kg")
                    ->modalSubmitActionLabel('Simpan Restock'),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFishTypes::route('/'),
            'create' => Pages\CreateFishType::route('/create'),
            'edit'   => Pages\EditFishType::route('/{record}/edit'),
        ];
    }
}
