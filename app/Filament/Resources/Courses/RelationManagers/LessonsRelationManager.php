<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    protected static ?string $title = 'الدروس المسجلة';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-play-circle';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type->value === 'recorded';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->label('عنوان الدرس')->required()->columnSpanFull(),
            Select::make('course_section_id')
                ->label('الوحدة')
                ->relationship('section', 'title', fn ($query) => $query->where('course_id', $this->getOwnerRecord()->id))
                ->createOptionForm([TextInput::make('title')->label('اسم الوحدة')->required()])
                ->createOptionUsing(fn (array $data) => $this->getOwnerRecord()->sections()->create($data)->id),
            TextInput::make('video_id')
                ->label('Bunny Stream Video ID')
                ->helperText('يُعرض عبر بث آمن مع علامة مائية ديناميكية باسم الطالب.')
                ->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('duration_minutes')->label('المدة (دقيقة)')->numeric()->default(10),
            TextInput::make('sort')->label('الترتيب')->numeric()->default(fn () => $this->getOwnerRecord()->lessons()->max('sort') + 1),
            Toggle::make('is_preview')->label('درس مجاني للمعاينة'),
            Textarea::make('content')->label('ملخص / مرفقات')->rows(3)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('sort')->label('#')->extraAttributes(['class' => 'cx-mono'])->width('3rem'),
                TextColumn::make('title')->label('الدرس')->description(fn ($record) => $record->section?->title)->wrap(),
                TextColumn::make('duration_minutes')->label('المدة')->suffix(' د')->extraAttributes(['class' => 'cx-mono']),
                IconColumn::make('has_video')->label('فيديو')->state(fn ($record) => filled($record->video_id))->boolean(),
                IconColumn::make('is_preview')->label('معاينة')->boolean(),
            ])
            ->headerActions([CreateAction::make()->label('إضافة درس')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
