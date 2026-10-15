<?php

namespace App\Filament\Resources\Payments;

use App\Enums\CourseType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Filament\Resources\Payments\Pages\ManagePayments;
use App\Filament\Support\NavGroup;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Revenue: automatic online payments + manual bookings entered by Sales (approved by admin). */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::FINANCE;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'دفعة';

    protected static ?string $pluralModelLabel = 'الإيرادات والحجوزات';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        // Sales have restricted finance access: only the bookings they recorded.
        return parent::getEloquentQuery()
            ->when($user && ! $user->isSuperAdmin(), fn ($q) => $q->where('recorded_by', $user->id));
    }

    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->isSuperAdmin()) {
            return null;
        }

        $pending = Payment::where('status', PaymentStatus::Pending)->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('user_id')
                ->label('الطالب')
                ->options(fn () => User::role(Role::Student->value)->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),
            Select::make('course_id')
                ->label('الكورس')
                ->options(fn () => Course::orderBy('title')->pluck('title', 'id'))
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set, $state) {
                    $course = Course::find($state);
                    $set('channel', $course?->type?->value);
                    $set('gross_amount', $course?->effectivePrice());
                    $set('amount', $course?->effectivePrice());
                }),
            Select::make('channel')->label('مصدر الدخل')->options(CourseType::class)->required(),
            Select::make('method')->label('طريقة الدفع')->options(PaymentMethod::class)->default(PaymentMethod::Cash->value)->required(),
            TextInput::make('gross_amount')->label('المبلغ قبل الخصم')->numeric()->required()->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, Get $get) => $set('amount', (float) $get('gross_amount') - (float) $get('discount_amount'))),
            TextInput::make('discount_amount')->label('الخصم')->numeric()->default(0)->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, Get $get) => $set('amount', (float) $get('gross_amount') - (float) $get('discount_amount'))),
            TextInput::make('amount')->label('صافي المبلغ')->numeric()->required()->prefix('ج.م'),
            DateTimePicker::make('paid_at')->label('تاريخ الدفع')->default(now())->seconds(false)->required(),
            TextInput::make('reference')->label('رقم الإيصال / المرجع'),
            Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('paid_at', 'desc')
            ->columns([
                TextColumn::make('paid_at')->label('التاريخ')->dateTime('d M Y')->sortable(),
                TextColumn::make('user.name')->label('الطالب')->searchable()->placeholder('—'),
                TextColumn::make('course.title')->label('الكورس')->limit(28)->toggleable(),
                TextColumn::make('channel')->label('المصدر')->badge(),
                TextColumn::make('source')->label('النوع')->formatStateUsing(fn ($state) => $state === 'manual' ? 'حجز يدوي' : 'إلكتروني')->badge()->color(fn ($state) => $state === 'manual' ? 'gray' : 'primary'),
                TextColumn::make('discount_amount')->label('خصم')->numeric(0)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount')->label('الصافي')->numeric(2)->suffix(' ج.م')->sortable()->extraAttributes(['class' => 'cx-mono'])
                    ->summarize(Sum::make()->label('الإجمالي')->numeric(2)),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('recorder.name')->label('أدخله')->placeholder('النظام')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')->label('مصدر الدخل')->options(CourseType::class),
                SelectFilter::make('status')->label('الحالة')->options(PaymentStatus::class),
                SelectFilter::make('source')->label('النوع')->options(['online' => 'إلكتروني', 'manual' => 'حجز يدوي']),
                Filter::make('this_month')->label('هذا الشهر')->toggle()
                    ->query(fn (Builder $q) => $q->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record) => $record->status === PaymentStatus::Pending)
                    ->authorize(fn (Payment $record) => auth()->user()->can('approve', $record))
                    ->action(function (Payment $record) {
                        $record->update(['status' => PaymentStatus::Paid, 'approved_at' => now(), 'approved_by' => auth()->id()]);
                        $record->user?->enrollments()->firstOrCreate(['course_id' => $record->course_id], ['enrolled_at' => now()]);
                        Notification::make()->title('تم اعتماد الحجز وتفعيل اشتراك الطالب')->success()->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePayments::route('/')];
    }
}
