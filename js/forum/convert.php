<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// $default = str_replace('flarum-ext-', '', $composer->name);
$prefix = readline('Module prefix: ');

// if (! $prefix) {
//     $prefix = $default;
// }

$prefixRegex = $prefix ? preg_quote($prefix, '/').'\/' : '';

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
$compat = '';

foreach ($rii as $file) {
    if ($file->isDir() || $file->getExtension() !== 'js') {
        continue;
    }

    $path = substr($file->getPathname(), strlen(__DIR__) + 1, -3);
    if ($path !== 'index') {
        $compat .= 'import '.$file->getBasename('.js')." from './$path';\n";
    }

    $content = file_get_contents($file->getRealPath());
    $dirs = explode('/', $path);
    array_pop($dirs);

    $content = preg_replace_callback("/^import (.+) from '$prefixRegex([^\.]+)';$/m", function ($matches) use ($dirs) {
        $importedDirs = explode('/', $matches[2]);
        $file = array_pop($importedDirs);
        $path = '';

        for ($i = 0; $i < count($dirs); $i++) {
            if (! isset($importedDirs[$i]) || $dirs[$i] !== $importedDirs[$i]) {
                return;
            }
        }

        for ($j = $i; $j < count($dirs); $j++) {
            $path .= '../';
        }
        for ($j = $i; $j < count($importedDirs); $j++) {
            $path .= $importedDirs[$j].'/';
        }

        if ($path[0] !== '.') {
            $path = './'.$path;
        }

        return "import {$matches[1]} from '$path$file';";
    }, $content);

    file_put_contents($file->getRealPath(), $content);
}

$compat .= "\nexport default {\n";

foreach ($rii as $file) {
    if ($file->isDir() || $file->getExtension() !== 'js') {
        continue;
    }

    $path = substr($file->getPathname(), strlen(__DIR__) + 1, -3);
    if ($path !== 'index') {
        $compat .= "  '$path': ".$file->getBasename('.js').",\n";
    }
}

$compat .= "};\n";

if (! file_exists('compat.js')) {
    file_put_contents('compat.js', $compat);
}
