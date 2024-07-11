<?php

namespace Dewsign\NovaPages\Nova;

use Laravel\Nova\Resource;
use Laravel\Nova\Fields\ID;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Dewsign\NovaPages\NovaPages;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\MorphMany;
use Benjaminhirsch\NovaSlugField\Slug;
use Laravel\Nova\Fields\BelongsToMany;
use Dewsign\NovaPages\Nova\PageRepeaters;
use Laravel\Nova\Http\Requests\NovaRequest;
use Dewsign\NovaPages\Nova\Filters\PageType;
use Benjaminhirsch\NovaSlugField\TextWithSlug;
use Dewsign\NovaPages\Nova\Filters\ActiveState;
use Maxfactor\Support\Webpage\Nova\MetaAttributes;
use Silvanite\NovaToolPermissions\Nova\AccessControl;

class Page extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'Dewsign\NovaPages\Models\Page';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'name',
        'summary',
    ];

    public static $group = 'Pages';

    /**
     * Get the logical group associated with the resource.
     *
     * @return string
     */
    public static function group()
    {
        return config('novapages.group', static::$group);
    }

    public static function label()
    {
        return __('Pages');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            $this->templateOptions(),
            Boolean::make('Active')->sortable()->rules('required', 'boolean'),
            Boolean::make('Featured')->sortable()->rules('required', 'boolean'),
            Number::make('Priority')->sortable()->rules('required', 'integer'),
            $this->languageOptions(),
            TextWithSlug::make('Name')->sortable()->rules('required_if:active,1', 'max:254')->slug('slug'),
            Slug::make('Slug')->sortable()->rules('required', 'alpha_dash', 'max:254')->hideFromIndex(),
            BelongsTo::make('Parent', 'parent', self::class)->nullable()->searchable()->rules('not_in:{{resourceId}}')->singularLabel('Parent'),
            Text::make('Full Path', function () {
                return $this->mapped_url;
            })->hideFromIndex(),
            $this->imageField(),
            $this->videoField(),
            Textarea::make('Summary'),
            HasMany::make('Child Pages', 'children', self::class),
            MorphMany::make(__('Repeaters'), 'repeaters', PageRepeaters::class),
            MetaAttributes::make(),
            AccessControl::make(),
        ];
    }

    private function templateOptions()
    {
        $options = NovaPages::availableTemplates();

        if (count($options) <= 1) {
            return $this->merge([]);
        }

        return $this->merge([
            Select::make('Template')
                ->options($options)
                ->displayUsingLabels()
                ->hideFromIndex(),
        ]);
    }

    /**
     * Show the video field if enabled in the config
     *
     * @return mixed
     */
    private function videoField()
    {
        if (config('novapages.videos.disabled')) {
            return $this->merge([]);
        }

        return $this->merge([
            config('novapages.videos.field')::make('Video'),//->disk(config('novapages.videos.disk', 'public')),
        ]);
    }

    /**
     * Show the image field if enabled in the config
     *
     * @return mixed
     */
    private function imageField()
    {
        if (config('novapages.images.disabled')) {
            return $this->merge([]);
        }

        return $this->merge([
            config('novapages.images.field')::make('Image')->disk(config('novapages.images.disk', 'public')),
            Text::make('Alternative Text')->rules('nullable', 'max:254')->hideFromIndex(),
        ]);
    }

    private function languageOptions()
    {
        if (!config('novapages.enableLanguageSelection')) {
            return $this->merge([]);
        }

        $options = NovaPages::availableLanguages();

        return $this->merge([
            Text::make('Language', function () {
                return country($this->formatLanguageCode())->getEmoji();
            }),
            Select::make(__('Language'), 'language')
                ->options($options)
                ->displayUsingLabels()
                ->hideFromIndex(),
        ]);
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [
            new ActiveState,
            new PageType,
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
