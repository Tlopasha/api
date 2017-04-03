<?php

/**
 * Part of the Antares Project package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Api
 * @version    0.9.0
 * @author     Antares Team
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017, Antares Project
 * @link       http://antaresproject.io
 */

namespace Antares\Api;

use Antares\Foundation\Support\Providers\ModuleServiceProvider;
use Antares\Api\Http\Presenters\Factory as PresenterFactory;
use Antares\Api\Http\Router\Adapter as RouterAdapter;
use Antares\Users\Http\Handlers\AccountPlaceholder;
use Antares\Api\Listener\PublicDriverListener;
use Antares\Control\Http\Handlers\ControlPane;
use Antares\Api\Http\Middleware\ApiMiddleware;
use Antares\Api\Services\AuthProviderService;
use Antares\Api\Http\Router\ControllerFinder;
use Dingo\Api\Routing\Router as ApiRouter;
use Antares\Api\Http\Handlers\MenuUser;
use Antares\Api\Listener\UserConfig;
use Antares\Api\Listener\RoleConfig;
use Antares\Api\Model\ApiRoles;
use Antares\Acl\RoleActionList;
use Illuminate\Routing\Router;
use Antares\Model\Role;
use Antares\Acl\Action;
use App;

class ApiServiceProvider extends ModuleServiceProvider
{

    /**
     * The application or extension namespace.
     *
     * @var string|null
     */
    protected $namespace = 'Antares\Api\Http\Controllers\Admin';

    /**
     * The application or extension group namespace.
     *
     * @var string|null
     */
    protected $routeGroup = 'antares/api';

    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'antares.form: user.api'         => [UserConfig::class],
        'antares.form: role.*'           => [RoleConfig::class],
        'eloquent.saved: ' . Role::class => 'Antares\Api\Listener\RoleConfig@onSave',
        'api.driver.public.update'       => [PublicDriverListener::class]
    ];

    /**
     * Registers service provider
     */
    public function register()
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__ . '/../resources/config/config.php', 'antares/api::config');

        $this->app->singleton(PresenterFactory::class, function($app) {
            $config = config('antares/api::config', []);
            return new PresenterFactory($app, $config);
        });

        $this->app->singleton(AuthProviderService::class, function($app) {
            $config = config('antares/api::config.auth.drivers', []);
            return new AuthProviderService($app, $app['cache.store'], $config);
        });
    }

    /**
     * Boots service provider
     */
    public function boot()
    {
        parent::boot();
        $router = $this->app->make(Router::class);


        $this->app->bind(RouterAdapter::class, function($app) {
            return new RouterAdapter($app->make(ApiRouter::class), $app->make(ControllerFinder::class), $app['config']['api']);
        });
        $this->app->bind('api-roles', function() {
            return new ApiRoles();
        });
        if (!App::runningInConsole()) {
            $this->registerApiAuth($this->app->make(AuthProviderService::class));
        }
        $router->pushMiddlewareToGroup('api', ApiMiddleware::class);
        $this->app->make('view')->composer(['antares/api::admin.user.*'], AccountPlaceholder::class);
        $this->app->make('view')->composer(['antares/foundation::account.*', 'antares/logger::admin.devices.*', 'antares/api::admin.user.*'], MenuUser::class);
        $this->app->make('view')->composer('antares/api::admin.configuration.*', ControlPane::class);
    }

    /**
     * Api auth register
     * 
     * @param AuthProviderService $authProviderService
     */
    protected function registerApiAuth(AuthProviderService $authProviderService)
    {
        foreach ($authProviderService->getEnabledDrivers() as $driver) {
            $driver->registerAuth();
        }
    }

    /**
     * @return RoleActionList
     */
    public static function acl()
    {
        $actions = [
            new Action('admin.api.configuration.*', 'Configuration'),
            new Action('api.*', 'Can Use Api'),
        ];

        $permissions = new RoleActionList;
        $permissions->add(Role::admin()->name, $actions);

        return $permissions;
    }

}
