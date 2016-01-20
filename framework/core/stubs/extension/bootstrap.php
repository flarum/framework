<?php

use Illuminate\Contracts\Events\Dispatcher;
use {{namespace}}\Listener;

// Return a function that registers the extension with Flarum. This is
// the place to listen to events, register bindings with the container
// and execute code when the application boots.
//
// Any typehinted argument of this function is automatically resolved
// by the IoC container.
return function (Dispatcher $events) {
    $events->subscribe(Listener\AddClientAssets::class);
};
