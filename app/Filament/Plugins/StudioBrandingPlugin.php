<?php

namespace App\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

/**
 * A panel plugin bundles configuration so several panels can share it.
 *
 * Everything here could be written inline in a panel provider — the value is that
 * it is written once, versioned, and applied identically to every panel.
 */
class StudioBrandingPlugin implements Plugin
{
    protected string $studioName = 'ProjectFlow';

    protected ?string $supportUrl = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function studioName(string $name): static
    {
        $this->studioName = $name;

        return $this;
    }

    public function supportUrl(string $url): static
    {
        $this->supportUrl = $url;

        return $this;
    }

    public function getId(): string
    {
        return 'studio-branding';
    }

    /**
     * Runs while the panel is being configured.
     */
    public function register(Panel $panel): void
    {
        $panel
            ->brandName($this->studioName)
            ->colors([
                'gray' => Color::Slate,
            ])
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => view('filament.hooks.environment-banner')->render(),
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): Htmlable => $this->footer(),
            );
    }

    /**
     * Runs after every panel has been registered — the place for anything that
     * needs the fully-built panel, or other plugins, to exist first.
     */
    public function boot(Panel $panel): void
    {
        //
    }

    protected function footer(): Htmlable
    {
        $year = now()->year;

        $support = $this->supportUrl
            ? ' · <a href="'.e($this->supportUrl).'" class="fi-link">Get help</a>'
            : '';

        return new HtmlString(
            '<div class="fi-section-content px-6 py-4 text-center text-xs text-gray-500 dark:text-gray-400">'
            .e($this->studioName).' — © '.$year.$support
            .'</div>'
        );
    }
}
