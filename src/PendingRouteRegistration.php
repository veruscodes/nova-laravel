<?php

namespace Laravel\Nova;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;
use Laravel\Nova\Http\Controllers\Fortify\AuthenticatedSessionController;
use Laravel\Nova\Http\Controllers\Fortify\ConfirmablePasswordController;
use Laravel\Nova\Http\Controllers\Fortify\NewPasswordController;
use Laravel\Nova\Http\Controllers\Fortify\PasswordController;
use Laravel\Nova\Http\Controllers\Fortify\PasswordResetLinkController;
use Laravel\Nova\Http\Controllers\Fortify\TwoFactorAuthenticatedSessionController;
use Laravel\Nova\Http\Controllers\Pages\AttachableController;
use Laravel\Nova\Http\Controllers\Pages\AttachedResourceUpdateController;
use Laravel\Nova\Http\Controllers\Pages\DashboardController;
use Laravel\Nova\Http\Controllers\Pages\Error403Controller;
use Laravel\Nova\Http\Controllers\Pages\Error404Controller;
use Laravel\Nova\Http\Controllers\Pages\HomeController;
use Laravel\Nova\Http\Controllers\Pages\LensController;
use Laravel\Nova\Http\Controllers\Pages\ResourceCreateController;
use Laravel\Nova\Http\Controllers\Pages\ResourceDetailController;
use Laravel\Nova\Http\Controllers\Pages\ResourceIndexController;
use Laravel\Nova\Http\Controllers\Pages\ResourceReplicateController;
use Laravel\Nova\Http\Controllers\Pages\ResourceUpdateController;
use Laravel\Nova\Http\Controllers\Pages\UserSecurityController;

class PendingRouteRegistration
{
    public string|false $loginPath = false;

    public string|false $logoutPath = false;

    public string|false $forgotPasswordPath = false;

    public string|false $resetPasswordPath = false;

    /**
     * Indicates if Nova is being used to authenticate users.
     */
    public bool $withAuthentication = false;

    /**
     * Indicates if Nova is being used to reset passwords.
     */
    public bool $withPasswordReset = false;

    /**
     * The authentication default.
     */
    public bool $withDefaultAuthentication = false;

    /**
     * Indicate if Nova is being used to handle e-mail verification.
     */
    public bool $withEmailVerification = false;

    /**
     * Indicate if Nova should enable email enumeration.
     */
    public bool $withPasswordResetPreventsEmailEnumeration = false;

    /**
     * The authentications middlewares.
     *
     * @var array<int, class-string|string>
     */
    protected $authenticationMiddlewares = ['nova'];

    /**
     * The password reset middlewares.
     *
     * @var array<int, class-string|string>
     */
    protected $passwordResetMiddlewares = ['nova'];

    /**
     * Register the Nova authentication routes.
     *
     * @param  array<int, class-string|string>  $middleware
     * @return $this
     */
    public function withAuthenticationRoutes(array $middleware = ['nova:auth'], bool $default = false)
    {
        $this->withAuthentication = true;

        $this->authenticationMiddlewares = $middleware;
        $this->withDefaultAuthentication = $default;

        if ($default === true) {
            Fortify::ignoreRoutes();
        }

        return $this;
    }

    /**
     * Register Nova without authentication routes.
     *
     * @param  array<int, class-string|string>  $middleware
     * @return $this
     */
    public function withoutAuthenticationRoutes(
        string|false $login = '/login',
        string|false $logout = '/logout',
        array $middleware = ['nova:auth']
    ) {
        $this->authenticationMiddlewares = $middleware;
        $this->withAuthentication = false;
        $this->withDefaultAuthentication = false;

        $this->loginPath = $login;
        $this->logoutPath = $logout;

        return $this;
    }

    /**
     * Register the Nova password reset routes.
     *
     * @param  array<int, class-string|string>  $middleware
     * @return $this
     */
    public function withPasswordResetRoutes(array $middleware = ['nova:auth'], bool $preventsEmailEnumeration = false)
    {
        $this->withPasswordReset = true;
        $this->withPasswordResetPreventsEmailEnumeration = $preventsEmailEnumeration;

        $this->passwordResetMiddlewares = $middleware;

        return $this;
    }

    /**
     * Register Nova without password reset routes.
     *
     * @return $this
     */
    public function withoutPasswordResetRoutes(
        string|false $forgotPassword = '/forgot-password',
        string|false $resetPassword = '/reset-password',
        array $middleware = ['nova:auth']
    ) {
        $this->passwordResetMiddlewares = $middleware;
        $this->withPasswordReset = false;

        $this->forgotPasswordPath = $forgotPassword;
        $this->resetPasswordPath = $resetPassword;

        return $this;
    }

    /**
     * Register Nova with e-mail verification routes.
     *
     * @return $this
     */
    public function withEmailVerificationRoutes()
    {
        $this->withEmailVerification = true;

        return $this;
    }

    /**
     * Register Nova without e-mail verification routes.
     *
     * @return $this
     */
    public function withoutEmailVerificationRoutes()
    {
        $this->withEmailVerification = false;

        return $this;
    }

    /**
     * Check if Nova is the default authentication.
     */
    public function defaultAuthentication(): bool
    {
        return $this->withDefaultAuthentication && $this->withAuthentication;
    }

    /**
     * Register the Nova routes.
     *
     * @return $this
     */
    public function register()
    {
        Nova::fortify()->bootstrap();

        return $this;
    }

    /**
     * Bootstrap the registered Nova routes.
     */
    public function bootstrap(Application $app): void
    {
        $apiMiddlewares = config('nova.api_middleware', []);

        $this->bootstrapAuthenticationRoutes($app);
        $this->bootstrapPasswordResetRoutes($app);
        $this->bootstrapEmailVerificationRoutes($app);
        $this->bootstrapUserSecurityRoutes($app, $apiMiddlewares);
        $this->bootstrapConfirmPasswordRoutes($app, $apiMiddlewares);
        $this->bootstrapTwoFactorAuthenticationRoutes($app, $apiMiddlewares);

        Nova::router()
            ->group(static function (Router $router) {
                $router->get('/403', Error403Controller::class)->name('nova.pages.403');
                $router->get('/404', Error404Controller::class)->name('nova.pages.404');
            });

        Nova::router(middleware: $apiMiddlewares)
            ->as('nova.pages.')
            ->group(static function (Router $router) {
                $router->get('/', HomeController::class)->name('home');
                $router->redirect('dashboard', Nova::url('/'))->name('dashboard');
                $router->get('dashboards/{name}', DashboardController::class)->name('dashboard.custom');

                $router->get('resources/{resource}', ResourceIndexController::class)->name('index');
                $router->get('resources/{resource}/new', ResourceCreateController::class)->name('create');
                $router->get('resources/{resource}/{resourceId}', ResourceDetailController::class)->name('detail');
                $router->get('resources/{resource}/{resourceId}/edit', ResourceUpdateController::class)->name('edit');
                $router->get('resources/{resource}/{resourceId}/replicate', ResourceReplicateController::class)->name('replicate');
                $router->get('resources/{resource}/lens/{lens}', LensController::class)->name('lens');

                $router->get('resources/{resource}/{resourceId}/attach/{relatedResource}', AttachableController::class)->name('attach');
                $router->get('resources/{resource}/{resourceId}/edit-attached/{relatedResource}/{relatedResourceId}', AttachedResourceUpdateController::class)->name('edit-attached');
            });
    }

    /**
     * Bootstrap the registered Nova authentication routes.
     */
    protected function bootstrapAuthenticationRoutes(Application $app): void
    {
        if ($this->withAuthentication === false) {
            if (! empty($this->loginPath)) {
                Nova::router(middleware: $this->authenticationMiddlewares)
                    ->group(function (Router $router) {
                        $router->redirect('/login', $this->loginPath)->name('nova.pages.login');
                    });
            }

            return;
        }

        $limiter = transform(
            config('fortify.limiters.login'),
            fn ($name) => Util::isThrottleRequestLimiter($name) ? $name : null,
        );

        if (
            $this->withDefaultAuthentication === true
            && ! Route::has('login')
            && Nova::url('/login') !== '/login'
        ) {
            Route::redirect('/login', Nova::url('/login'))->name('login');
        }

        Nova::router(middleware: $this->authenticationMiddlewares)
            ->group(static function (Router $router) use ($limiter) {
                $router->get('/login', [AuthenticatedSessionController::class, 'create'])->name('nova.pages.login');
                $router->post('/login', [AuthenticatedSessionController::class, 'store'])
                    ->middleware(array_filter([$limiter ? 'throttle:'.$limiter : null]))
                    ->name('nova.login');
            });

        Nova::router()
            ->group(static function (Router $router) {
                $router->post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('nova.logout');
            });
    }

    /**
     * Bootstrap the registered Nova password reset routes.
     */
    protected function bootstrapPasswordResetRoutes(Application $app): void
    {
        if ($this->withPasswordReset === false) {
            if (! empty($this->forgotPasswordPath)) {
                Nova::router(middleware: $this->passwordResetMiddlewares)
                    ->group(function (Router $router) {
                        $router->redirect('/password/reset', $this->forgotPasswordPath)->name('nova.pages.password.email');
                    });
            }

            return;
        }

        Nova::router(middleware: $this->passwordResetMiddlewares)
            ->group(static function (Router $router) {
                $router->get('/password/reset', [PasswordResetLinkController::class, 'create'])->name('nova.pages.password.email');
                $router->post('/password/email', [PasswordResetLinkController::class, 'store'])->name('nova.password.email');

                $router->get('/password/reset/{token}', [NewPasswordController::class, 'create'])->name('nova.pages.password.reset');
                $router->post('/password/reset', [NewPasswordController::class, 'store'])->name('nova.password.reset');
            });
    }

    /**
     * Bootstrap the registered Nova email verification routes.
     */
    protected function bootstrapEmailVerificationRoutes(Application $app): void
    {
        if (! Nova::fortify()->enabled(Features::emailVerification()) || $this->withEmailVerification === false) {
            return;
        }

        $verificationLimiter = transform(
            config('fortify.limiters.verification', '6,1'),
            fn ($name) => Util::isThrottleRequestLimiter($name) ? $name : null,
        );

        $middlewares = [...$this->authenticationMiddlewares, 'nova.auth'];
        $middlewaresWithLimiter = [...$middlewares, "throttle:{$verificationLimiter}"];
        $hasVerifyRoute = Route::has('verification.verify');

        Nova::router(middleware: $middlewares)
            ->get('/email/verify', EmailVerificationPromptController::class)
            ->name('nova.pages.verification.notice');

        Nova::router(middleware: $middlewaresWithLimiter)
            ->group(static function (Router $router) use ($hasVerifyRoute) {
                if (! $hasVerifyRoute) {
                    $router->get('/email/verify/{id}/{hash}', VerifyEmailController::class)
                        ->name('verification.verify');
                }

                $router->post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                    ->name('nova.pages.verification.send');
            });
    }

    /**
     * Bootstrap the registered Nova confirm password routes.
     *
     * @param  array<int, string|class-string>  $apiMiddlewares
     */
    protected function bootstrapUserSecurityRoutes(Application $app, array $apiMiddlewares): void
    {
        if (Features::hasSecurityFeatures()) {
            Nova::router(middleware: $apiMiddlewares)
                ->group(static function (Router $router) {
                    $router->get('/user-security', [UserSecurityController::class, 'show'])
                        ->name('nova.pages.user-security');
                });
        }

        if (Nova::fortify()->enabled(Features::updatePasswords())) {
            Nova::router(middleware: $apiMiddlewares)
                ->group(static function (Router $router) {
                    $router->put('/user-security/password', [PasswordController::class, 'update']);
                });
        }
    }

    /**
     * Bootstrap the registered Nova confirm password routes.
     *
     * @param  array<int, string|class-string>  $apiMiddlewares
     */
    protected function bootstrapConfirmPasswordRoutes(Application $app, array $apiMiddlewares): void
    {
        Nova::router(middleware: $apiMiddlewares)
            ->group(static function (Router $router) {
                $router->get('/user-security/confirm-password', [ConfirmablePasswordController::class, 'show'])
                    ->name('nova.pages.password.verify');
                $router->get('/user-security/confirmed-password-status', [ConfirmedPasswordStatusController::class, 'show'])
                    ->name('nova.password.confirmation');
                $router->post('/user-security/confirm-password', [ConfirmablePasswordController::class, 'store'])
                    ->name('nova.password.confirm');
            });
    }

    /**
     * Bootstrap the registered Nova 2FA routes.
     *
     * @param  array<int, string|class-string>  $apiMiddlewares
     */
    protected function bootstrapTwoFactorAuthenticationRoutes(Application $app, array $apiMiddlewares): void
    {
        if (! Nova::fortify()->enabled(Features::twoFactorAuthentication())) {
            return;
        }

        $guard = Util::userGuard();

        $twoFactorLimiter = transform(
            config('fortify.limiters.two-factor'),
            fn ($name) => Util::isThrottleRequestLimiter($name) ? $name : null,
        );

        $middlewaresWithLimiter = array_filter([
            ...$this->authenticationMiddlewares,
            $twoFactorLimiter ? "throttle:{$twoFactorLimiter}" : null,
        ]);

        $twoFactorMiddlewares = array_filter([
            ...$apiMiddlewares,
            Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword') ? 'password.confirm' : null,
        ]);

        Nova::router(middleware: $middlewaresWithLimiter)
            ->group(static function (Router $router) {
                $router->get('/user-security/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'create'])->name('nova.two-factor.login');
                $router->post('/user-security/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'store']);
            });

        Nova::router(middleware: $twoFactorMiddlewares)
            ->group(static function (Router $router) {
                $router->post('/user-security/two-factor-authentication', [TwoFactorAuthenticationController::class, 'store']);
                $router->post('/user-security/confirmed-two-factor-authentication', [ConfirmedTwoFactorAuthenticationController::class, 'store']);
                $router->delete('/user-security/two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy']);
                $router->get('/user-security/two-factor-qr-code', [TwoFactorQrCodeController::class, 'show']);
                $router->get('/user-security/two-factor-secret-key', [TwoFactorSecretKeyController::class, 'show']);
                $router->get('/user-security/two-factor-recovery-codes', [RecoveryCodeController::class, 'index']);
                $router->post('/user-security/two-factor-recovery-codes', [RecoveryCodeController::class, 'store']);
            });
    }
}
