<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * One row per compiled asset, replacing the map that rev-manifest.json held in
 * a single JSON value. A revision can then be written on its own, so two
 * instances recording different assets cannot overwrite each other's work.
 *
 * The table starts empty on an existing install: nothing reads the manifest
 * file any more, so the first request finds no revision for an asset and
 * commits one, exactly as it would after a cache clear.
 */
return Migration::createTable(
    'asset_revisions',
    function (Blueprint $table) {
        // The asset's path as the compilers name it — 'forum.js', or a
        // code-split chunk like 'js/<extension>/forum/components/Foo.js',
        // whose length is bounded only by what extensions register. The
        // natural primary key, which is what makes a single-row upsert atomic.
        $table->string('file', 255)->primary();

        // A hash of the compiled output, or RevisionCompiler::EMPTY_REVISION
        // for a bundle that legitimately has no file. Never null: absence of a
        // revision is absence of the row.
        $table->string('revision', 32);
    }
);
