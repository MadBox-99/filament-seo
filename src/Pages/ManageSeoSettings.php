<?php

namespace Madbox99\FilamentSeo\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Madbox99\FilamentSeo\FilamentSeoPlugin;
use Madbox99\FilamentSeo\Models\SeoSetting;
use UnitEnum;

class ManageSeoSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?string $navigationLabel = 'Global SEO';

    protected static ?string $title = 'Global SEO Settings';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament-seo::pages.manage-seo-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    #[\Override]
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        if (filament()->hasPlugin('filament-seo')) {
            /** @var FilamentSeoPlugin $plugin */
            $plugin = filament()->getPlugin('filament-seo');

            return $plugin->getNavigationGroup() ?? static::$navigationGroup;
        }

        return static::$navigationGroup;
    }

    #[\Override]
    public static function getNavigationIcon(): string|BackedEnum|null
    {
        if (filament()->hasPlugin('filament-seo')) {
            /** @var FilamentSeoPlugin $plugin */
            $plugin = filament()->getPlugin('filament-seo');

            return $plugin->getNavigationIcon() ?? static::$navigationIcon;
        }

        return static::$navigationIcon;
    }

    #[\Override]
    public static function getNavigationSort(): ?int
    {
        if (filament()->hasPlugin('filament-seo')) {
            /** @var FilamentSeoPlugin $plugin */
            $plugin = filament()->getPlugin('filament-seo');

            return $plugin->getNavigationSort() ?? static::$navigationSort;
        }

        return static::$navigationSort;
    }

    public function mount(): void
    {
        $settings = SeoSetting::current();

        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Defaults')
                    ->schema([
                        Forms\Components\TextInput::make('default_title_pattern')
                            ->label('Title Pattern')
                            ->helperText('Available placeholders: {title}, {site_name}'),
                        Forms\Components\Textarea::make('default_description')
                            ->label('Default Meta Description')
                            ->rows(3)
                            ->maxLength(160),
                        Forms\Components\FileUpload::make('default_og_image')
                            ->label('Default OG Image')
                            ->image()
                            ->directory('seo'),
                    ]),
                Forms\Components\Section::make('Robots.txt')
                    ->schema([
                        Forms\Components\Textarea::make('robots_txt')
                            ->label('robots.txt Content')
                            ->rows(8),
                    ]),
                Forms\Components\Section::make('Sitemap')
                    ->schema([
                        Forms\Components\TagsInput::make('sitemap_excluded_urls')
                            ->label('Excluded URLs from Sitemap'),
                    ]),
                Forms\Components\Section::make('Schema.org')
                    ->schema([
                        Forms\Components\Select::make('schema_org_type')
                            ->label('Schema Type')
                            ->options([
                                'Organization' => 'Organization',
                                'LocalBusiness' => 'LocalBusiness',
                                'Corporation' => 'Corporation',
                                'Person' => 'Person',
                            ]),
                        Forms\Components\KeyValue::make('schema_org_data')
                            ->label('Schema.org Data'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = SeoSetting::current();

        $settings->update([
            'default_title_pattern' => $data['default_title_pattern'] ?? '{title} | {site_name}',
            'default_description' => $data['default_description'] ?? '',
            'default_og_image' => $data['default_og_image'] ?? '',
            'robots_txt' => $data['robots_txt'] ?? '',
            'sitemap_excluded_urls' => $data['sitemap_excluded_urls'] ?? [],
            'schema_org_type' => $data['schema_org_type'] ?? 'Organization',
            'schema_org_data' => $data['schema_org_data'] ?? [],
        ]);

        Notification::make()->title('Settings saved.')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Save')->submit('save'),
        ];
    }
}
