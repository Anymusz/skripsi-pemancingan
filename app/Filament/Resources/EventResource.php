<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    public static function getNavigationIcon(): string|null
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationLabel(): string
    {
        return 'Event & Informasi';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Konten';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationBadge(): ?string
    {
        if (!auth()->user()?->can('manage-events')) {
            return 'Dibatasi';
        }
        return null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        if (!auth()->user()?->can('manage-events')) {
            return 'danger';
        }
        return null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Event')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('category')
                            ->label('Kategori')
                            ->options([
                                'event'     => 'Event Pemancingan',
                                'informasi' => 'Informasi Umum',
                            ])
                            ->required()
                            ->default('informasi'),
                        Select::make('status')
                            ->label('Status Publikasi')
                            ->options([
                                'draft'          => 'Draft',
                                'dipublikasikan' => 'Dipublikasikan',
                            ])
                            ->required()
                            ->default('draft'),
                        DatePicker::make('event_date')
                            ->label('Tanggal Mulai')
                            ->nullable(),
                        DatePicker::make('end_date')
                            ->label('Tanggal Berakhir')
                            ->nullable()
                            ->after('event_date'),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(5)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'event'     => 'info',
                        'informasi' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'event'     => 'Event',
                        'informasi' => 'Informasi Umum',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'dipublikasikan' => 'success',
                        'draft'          => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'dipublikasikan' => 'Dipublikasikan',
                        'draft'          => 'Draft',
                    }),
                Tables\Columns\TextColumn::make('event_date')
                    ->label('Tanggal Mulai')
                    ->date('d M Y')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Tanggal Berakhir')
                    ->date('d M Y')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->options([
                        'event'     => 'Event Pemancingan',
                        'informasi' => 'Informasi Umum',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft'          => 'Draft',
                        'dipublikasikan' => 'Dipublikasikan',
                    ]),
            ])
            ->actions([
                // Toggle publish/draft action
                Tables\Actions\Action::make('togglePublish')
                    ->label(fn (Event $record): string => $record->status === 'dipublikasikan' ? 'Jadikan Draft' : 'Publikasikan')
                    ->icon(fn (Event $record): string => $record->status === 'dipublikasikan' ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Event $record): string => $record->status === 'dipublikasikan' ? 'gray' : 'success')
                    ->action(function (Event $record) {
                        $record->update([
                            'status' => $record->status === 'dipublikasikan' ? 'draft' : 'dipublikasikan',
                        ]);
                    })
                    ->requiresConfirmation(fn (Event $record): bool => $record->status !== 'dipublikasikan')
                    ->modalHeading('Publikasikan Event?')
                    ->modalDescription('Event ini akan ditampilkan kepada semua pengguna.'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit'   => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
