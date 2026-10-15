<?php

namespace App\Filament\Resources\WhatsappMessages;

use App\Filament\Resources\WhatsappMessages\Pages\ManageWhatsappMessages;
use App\Filament\Support\NavGroup;
use App\Models\WhatsappMessage;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Outbound log of every automated WhatsApp trigger. */
class WhatsappMessageResource extends Resource
{
    protected static ?string $model = WhatsappMessage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::SETTINGS;

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'رسالة واتساب';

    protected static ?string $pluralModelLabel = 'سجل رسائل واتساب';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('الوقت')->dateTime('d M — h:i A'),
                TextColumn::make('to')->label('إلى')->extraAttributes(['dir' => 'ltr', 'class' => 'cx-mono']),
                TextColumn::make('event')->label('الحدث')->badge()->color('gray'),
                TextColumn::make('body')->label('الرسالة')->limit(70)->wrap(),
                TextColumn::make('provider')->label('المزود'),
                TextColumn::make('status')->label('الحالة')->badge()->color(fn ($state) => match ($state) {
                    'sent' => 'success', 'failed' => 'danger', default => 'gray'
                }),
            ])
            ->filters([
                SelectFilter::make('event')->label('الحدث')->options(fn () => WhatsappMessage::distinct()->pluck('event', 'event')->filter()),
                SelectFilter::make('status')->label('الحالة')->options(['sent' => 'أُرسلت', 'failed' => 'فشلت', 'queued' => 'بالانتظار']),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageWhatsappMessages::route('/')];
    }
}
