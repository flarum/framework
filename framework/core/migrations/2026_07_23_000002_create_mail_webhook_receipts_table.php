<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('mail_webhook_receipts', function (Blueprint $table) {
    // Keep only a fingerprint; receipts must not retain recipients or payloads.
    $table->string('id', 64)->primary();
});
