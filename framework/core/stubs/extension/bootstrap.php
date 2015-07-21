<?php

// Require the extension's composer autoload file. This will enable all of our
// classes in the src directory to be autoloaded.
require __DIR__.'/vendor/autoload.php';

// Return the name of our Extension class. Flarum will register it as a service
// provider, allowing it to register bindings and execute code when the
// application boots.
return '{{namespace}}\Extension';
