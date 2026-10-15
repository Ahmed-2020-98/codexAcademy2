<?php

namespace App\Filament\Resources\Leads;

use App\Enums\CourseType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\Role;
use App\Filament\Resources\Leads\Actions\LeadActions;
use App\Filament\Resources\Leads\Pages\CreateLead;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Support\NavGroup;
use App\Models\Lead;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::CRM;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'طالب محتمل';

    protected static ?string $pluralModelLabel = 'سجل الطلاب (CRM)';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $count = Lead::overdue()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'متأخرون عن الـ SLA';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone', 'email'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات الطالب')
                ->icon('heroicon-o-identification')
                ->columns(3)
                ->schema([
                    TextInput::make('name')->label('الاسم')->required()->maxLength(255),
                    TextInput::make('phone')->label('رقم الهاتف / واتساب')->tel()->required(),
                    TextInput::make('email')->label('البريد الإلكتروني')->email(),
                ]),
            Section::make('الاهتمام الدراسي')
                ->icon('heroicon-o-academic-cap')
                ->columns(3)
                ->schema([
                    Select::make('department_id')->label('القسم')->relationship('department', 'name')->preload(),
                    Select::make('course_id')->label('الكورس')->relationship('course', 'title')->searchable()->preload(),
                    Select::make('study_mode')->label('نوع الدراسة')->options(CourseType::class),
                    Select::make('source')->label('المصدر')->options(LeadSource::class)->default(LeadSource::Manual->value)->required()->visibleOn('create'),
                    Select::make('assigned_to')
                        ->label('مسؤول المتابعة')
                        ->options(fn () => User::role([Role::Sales->value, Role::SuperAdmin->value])->pluck('name', 'id'))
                        ->default(fn () => auth()->id()),
                    DateTimePicker::make('assessment_at')->label('موعد اختبار المستوى')->seconds(false)->visibleOn('edit'),
                ]),
            Textarea::make('notes')->label('ملاحظات')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                Section::make('ملف الطالب')
                    ->icon('heroicon-o-identification')
                    ->columnSpan(2)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('status')->label('الحالة')->badge(),
                        TextEntry::make('sla')
                            ->label('مؤشر SLA')
                            ->state(fn (Lead $record) => static::slaLabel($record))
                            ->badge()
                            ->color(fn (Lead $record) => static::slaColor($record)),
                        TextEntry::make('source')->label('المصدر')->badge()->color('gray'),
                        TextEntry::make('phone')->label('الهاتف')->copyable()->extraAttributes(['dir' => 'ltr']),
                        TextEntry::make('email')->label('البريد')->placeholder('—'),
                        TextEntry::make('assignee.name')->label('مسؤول المتابعة')->placeholder('غير مُسند'),
                        TextEntry::make('course.title')->label('الكورس')->placeholder('—'),
                        TextEntry::make('study_mode')->label('نوع الدراسة')->badge()->placeholder('—'),
                        TextEntry::make('department.name')->label('القسم')->placeholder('—'),
                        TextEntry::make('assessment_at')->label('اختبار المستوى')->dateTime('d M Y — h:i A')->placeholder('لم يُحدد'),
                        TextEntry::make('assessment_level')->label('المستوى')->placeholder('—'),
                        TextEntry::make('created_at')->label('تاريخ التسجيل')->dateTime('d M Y — h:i A'),
                        TextEntry::make('notes')->label('ملاحظات')->placeholder('—')->columnSpanFull(),
                    ]),
                Section::make('الانسحاب')
                    ->icon('heroicon-o-x-circle')
                    ->columnSpan(1)
                    ->visible(fn (Lead $record) => $record->status === LeadStatus::DroppedOut)
                    ->schema([
                        TextEntry::make('dropoutReason.name')->label('السبب')->badge()->color('danger'),
                        TextEntry::make('dropout_notes')->label('التفاصيل')->placeholder('—'),
                        TextEntry::make('dropout_approved_at')
                            ->label('الاعتماد')
                            ->formatStateUsing(fn ($state, Lead $record) => $state ? 'معتمد من '.$record->dropoutApprover?->name : 'بانتظار اعتماد الإدارة')
                            ->placeholder('بانتظار اعتماد الإدارة')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'warning'),
                    ]),
            ])->columnSpanFull(),
            Section::make('سجل المتابعة')
                ->icon('heroicon-o-clock')
                ->schema([
                    ViewEntry::make('activities')->view('filament.leads.timeline')->hiddenLabel(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('الطالب')
                    ->description(fn (Lead $record) => $record->phone)
                    ->searchable(['name', 'phone', 'email'])
                    ->weight('medium'),
                TextColumn::make('course.title')->label('الكورس')->limit(30)->placeholder('—')->toggleable(),
                TextColumn::make('study_mode')->label('النوع')->badge()->toggleable(),
                TextColumn::make('status')->label('الحالة')->badge()->sortable(),
                TextColumn::make('sla_due_at')
                    ->label('SLA')
                    ->state(fn (Lead $record) => static::slaLabel($record))
                    ->badge()
                    ->color(fn (Lead $record) => static::slaColor($record))
                    ->sortable(),
                TextColumn::make('assignee.name')->label('المسؤول')->placeholder('—')->toggleable(),
                TextColumn::make('source')->label('المصدر')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dropoutReason.name')->label('سبب الانسحاب')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('التسجيل')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(LeadStatus::class)->multiple(),
                SelectFilter::make('assigned_to')->label('المسؤول')->relationship('assignee', 'name'),
                SelectFilter::make('department_id')->label('القسم')->relationship('department', 'name'),
                SelectFilter::make('source')->label('المصدر')->options(LeadSource::class),
                SelectFilter::make('dropout_reason_id')->label('سبب الانسحاب')->relationship('dropoutReason', 'name'),
                Filter::make('overdue')->label('المتأخرون فقط')->toggle()->query(fn (Builder $q) => $q->overdue()),
            ])
            ->recordActions([
                LeadActions::changeStatus()->iconButton()->tooltip('تغيير الحالة'),
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    LeadActions::whatsapp(),
                    LeadActions::approveDropout(),
                ]),
            ])
            ->recordUrl(fn (Lead $record) => static::getUrl('view', ['record' => $record]));
    }

    public static function slaLabel(Lead $record): string
    {
        $seconds = $record->slaSecondsLeft();

        if ($seconds === null) {
            return $record->first_contacted_at && $record->sla_due_at && $record->first_contacted_at->gt($record->sla_due_at)
                ? 'تم التواصل متأخراً'
                : 'تم التواصل';
        }

        $hours = intdiv(abs($seconds), 3600);
        $minutes = intdiv(abs($seconds) % 3600, 60);

        return $seconds >= 0 ? "متبقي {$hours}س {$minutes}د" : "متأخر {$hours}س {$minutes}د";
    }

    public static function slaColor(Lead $record): string
    {
        $seconds = $record->slaSecondsLeft();

        return match (true) {
            $seconds === null => $record->first_contacted_at?->gt($record->sla_due_at ?? now()) ? 'warning' : 'success',
            $seconds < 0 => 'danger',
            $seconds < 4 * 3600 => 'warning',
            default => 'info',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'view' => ViewLead::route('/{record}'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }
}
