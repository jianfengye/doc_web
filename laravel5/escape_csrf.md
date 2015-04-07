# 如何针对某个路由解除csrf验证

Laravel5种默认是开启csrf验证的。这个就代表着所有POST的路由都会引发csrf验证，那如果我们有某些路由不想要csrf验证怎么办呢？

## laravel5的csrf验证

laravel5的csrf验证可以看app/Http/Kernel.php：
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
      'Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode',
      'Illuminate\Cookie\Middleware\EncryptCookies',
      'Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse',
      'Illuminate\Session\Middleware\StartSession',
      'Illuminate\View\Middleware\ShareErrorsFromSession',
      'App\Http\Middleware\VerifyCsrfToken',
    ];

