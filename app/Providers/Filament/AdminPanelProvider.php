<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->maxContentWidth(MaxWidth::Full)
            ->colors([
                'primary' => Color::hex('#8234E9'),
                'gray' => Color::hex('#303040'),
                'success' => Color::hex('#29D57B'),
                'warning' => Color::Yellow,
                'danger' => Color::Red,
                'info' => Color::Blue,
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.hooks.head-end'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Development')
                    ->collapsed(),
            ])
            ->navigationItems([
                NavigationItem::make('Backlog')
                    ->group('Development')
                    ->icon('heroicon-o-queue-list')
                    ->url(fn () => \App\Filament\Pages\Development\Backlog::getUrl())
                    ->visible(fn () => $this->showDevelopmentMenu()),
                NavigationItem::make('Boards')
                    ->group('Development')
                    ->icon('heroicon-o-view-columns')
                    ->url(fn () => \App\Filament\Pages\Development\Boards::getUrl())
                    ->visible(fn () => $this->showDevelopmentMenu()),
                NavigationItem::make('Issues')
                    ->group('Development')
                    ->icon('heroicon-o-bug-ant')
                    ->url(fn () => \App\Filament\Pages\Development\Issues::getUrl())
                    ->visible(fn () => $this->showDevelopmentMenu()),
                NavigationItem::make('Sprints')
                    ->group('Development')
                    ->icon('heroicon-o-calendar-days')
                    ->url(fn () => \App\Filament\Pages\Development\Sprints::getUrl())
                    ->visible(fn () => $this->showDevelopmentMenu()),
                NavigationItem::make('Repositories')
                    ->group('Development')
                    ->icon('heroicon-o-code-bracket-square')
                    ->url(fn () => \App\Filament\Pages\Development\Repositories::getUrl())
                    ->visible(fn () => $this->showDevelopmentMenu()),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    private function showDevelopmentMenu(): bool
    {
        if (! session()->has('current_project_id')) {
            return false;
        }

        return request()->routeIs('filament.admin.resources.boards.kanban')
            || request()->routeIs('filament.admin.pages.development.*');
    }
}
