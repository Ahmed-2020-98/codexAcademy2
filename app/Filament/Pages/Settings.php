<?php

namespace App\Filament\Pages;

use App\Filament\Support\NavGroup;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Settings extends Page
{
    protected string $view = 'filament.pages.settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::SETTINGS;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'إعدادات النظام';

    protected static ?string $title = 'إعدادات النظام';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(collect(Setting::DEFAULTS)->mapWithKeys(fn ($v, $k) => [$k => Setting::get($k)])->all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('مؤشر الاتفاقية (SLA)')->columns(2)->schema([
                TextInput::make('sla_hours')->label('مهلة أول تواصل (ساعات)')->numeric()->minValue(1)->required(),
                TextInput::make('academy_whatsapp')->label('رقم واتساب الأكاديمية')->tel(),
            ]),
            Section::make('الإحالة والمحتوى')->columns(3)->schema([
                TextInput::make('referral_discount_percent')->label('خصم كود الإحالة الافتراضي %')->numeric()->minValue(0)->maxValue(100),
                TextInput::make('watermark_interval_seconds')->label('تغيير موضع العلامة المائية كل (ثانية)')->numeric()->minValue(2),
                TextInput::make('currency')->label('العملة'),
            ]),
        ]);
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            Setting::put($key, $value);
        }

        Notification::make()->title('تم حفظ الإعدادات')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('save')->label('حفظ')->action('save')];
    }
}
