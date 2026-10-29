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
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Unexposed;
use Madbox99\FilamentSeo\FilamentSeoPlugin;
use Madbox99\FilamentSeo\Models\SeoSetting;
use UnitEnum;

class ManageSeoSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament-seo::pages.manage-seo-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    #[\Override]
    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? __('filament-seo::filament-seo.settings.navigation_label');
    }

    #[\Override]
    #[Unexposed]
    public function getTitle(): string
    {
        return static::$title ?? __('filament-seo::filament-seo.settings.title');
    }

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

    public function form(Form|Schema $form): Form|Schema
    {
        return $form
            ->schema([
                Schemas\Components\Section::make(__('filament-seo::filament-seo.settings.sections.defaults'))
                    ->schema([
                        Forms\Components\TextInput::make('default_title_pattern')
                            ->label(__('filament-seo::filament-seo.settings.fields.default_title_pattern'))
                            ->helperText(__('filament-seo::filament-seo.settings.fields.default_title_pattern_help')),
                        Forms\Components\Textarea::make('default_description')
                            ->label(__('filament-seo::filament-seo.settings.fields.default_description'))
                            ->rows(3)
                            ->maxLength(160),
                        Forms\Components\FileUpload::make('default_og_image')
                            ->label(__('filament-seo::filament-seo.settings.fields.default_og_image'))
                            ->image()
                            ->disk(config('filament-seo.og_image_disk', 'public'))
                            ->directory('seo'),
                    ]),
                Schemas\Components\Section::make(__('filament-seo::filament-seo.settings.sections.robots_txt'))
                    ->schema([
                        Forms\Components\Textarea::make('robots_txt')
                            ->label(__('filament-seo::filament-seo.settings.fields.robots_txt'))
                            ->rows(8),
                    ]),
                Schemas\Components\Section::make(__('filament-seo::filament-seo.settings.sections.sitemap'))
                    ->schema([
                        Forms\Components\TagsInput::make('sitemap_excluded_urls')
                            ->label(__('filament-seo::filament-seo.settings.fields.sitemap_excluded_urls')),
                    ]),
                Schemas\Components\Section::make(__('filament-seo::filament-seo.settings.sections.schema_org'))
                    ->schema([
                        Forms\Components\Select::make('schema_org_type')
                            ->label(__('filament-seo::filament-seo.settings.fields.schema_org_type'))
                            ->options([
                                'Organization' => __('filament-seo::filament-seo.settings.schema_org_types.Organization'),
                                'LocalBusiness' => __('filament-seo::filament-seo.settings.schema_org_types.LocalBusiness'),
                                'Corporation' => __('filament-seo::filament-seo.settings.schema_org_types.Corporation'),
                                'Person' => __('filament-seo::filament-seo.settings.schema_org_types.Person'),
                            ]),
                        Forms\Components\KeyValue::make('schema_org_data')
                            ->label(__('filament-seo::filament-seo.settings.fields.schema_org_data')),
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

        Notification::make()->title(__('filament-seo::filament-seo.settings.notifications.saved'))->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label(__('filament-seo::filament-seo.settings.actions.save'))->submit('save'),
        ];
    }
}
