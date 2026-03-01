<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FishTypeResource\Pages;
use App\Models\FishType;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
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

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage-fish-stock') ?? false;
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
                            ->label('Stok (Kg)')
                            ->numeric()
                            ->suffix('kg')
                            ->required(),
                        TextInput::make('min_stock_threshold')
                            ->label('Batas Minimum Stok (Kg)')
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
                    ->color(fn (FishType $record): string => $record->isBelowThreshold() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('min_stock_threshold')
                    ->label('Min. Stok')
                    ->numeric(2)
                    ->suffix(' kg'),
                Tables\Columns\TextColumn::make('last_restocked_at')
                    ->label('Terakhir Restock')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum pernah'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFishTypes::route('/'),
            'create' => Pages\CreateFishType::route('/create'),
            'edit' => Pages\EditFishType::route('/{record}/edit'),
        ];
    }
}
