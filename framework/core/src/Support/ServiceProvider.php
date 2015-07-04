<?php namespace Flarum\Support;

use Flarum\Extend\ExtenderInterface;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->extend($this->extenders());
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * @return ExtenderInterface[]
     */
    public function extenders()
    {
        return [];
    }

    /**
     * @param ExtenderInterface|ExtenderInterface[] $extenders
     * @return void
     */
    protected function extend($extenders)
    {
        if (! is_array($extenders)) {
            $extenders = [$extenders];
        }

        foreach ($extenders as $extender) {
            if (! $extender instanceof ExtenderInterface) {
                throw new InvalidArgumentException('Argument must be an object of type '
                    . ExtenderInterface::class);
            }

            $extender->extend($this->app);
        }
    }
}
