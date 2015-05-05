<?php

// Require the extension's composer autoload file. This will enable all of our
// classes in the src directory to be autoloaded.
require 'vendor/autoload.php';

// Register our service provider with the Flarum application. In here we can
// register bindings and execute code when the application boots.
return $app->register('Flarum\Categories\CategoriesServiceProvider');
