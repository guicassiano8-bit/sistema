<?php

namespace App\Providers;

use App\Models\RewardRedemption;
use App\Models\ShoppingItem;
use App\Models\Task;
use App\Models\User;
use App\Services\ShoppingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.layouts.app', function ($view) {
            /** @var User $user */
            $user = Auth::user();

            $view->with('jogador', $user->jogador());
            $view->with('navBadges', ['inventario' => app(ShoppingService::class)->urgentesPendentes()]);
        });

        // Grava "task" em source_type em vez de "App\Models\Task".
        // Renomear ou mover um Model não quebra os registros antigos.
        Relation::enforceMorphMap([
            'task' => Task::class,
            'reward_redemption' => RewardRedemption::class,
            'shopping_item' => ShoppingItem::class,
        ]);

        // Fora de produção, gera erro em vez de falhar em silêncio quando:
        // - uma relação é carregada dentro de um loop (N+1) sem with();
        // - um atributo fora do $fillable é preenchido;
        // - um atributo que não existe é lido.
        /** @var Application $app */
        $app = $this->app;
        Model::shouldBeStrict(! $app->isProduction());
    }
}
