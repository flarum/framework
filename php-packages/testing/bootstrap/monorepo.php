<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

/*
|
| This is specific to a monorepo setup. If you're using a single
| repository for your extension, checkout the extension skeleton
| at https://github.com/flarum/cli/tree/main/boilerplate/skeleton/extension.
|
| ---------------------------------------------------------------
|
| We symlink local vendor bins to the framework vendor bin
| to be able to run scripts from the extension directory.
|
| We also use a `FLARUM_TEST_VENDOR_PATH` environment variable
| to tell each extension where to find the framework vendor,
| instead of a SetupScript property, because it is also needed
| when running the tests.
|
*/

$monorepoVendor = __DIR__.'/../../../vendor';

// The root directory of the extension where tests are run from.
$localVendor = getcwd().'/vendor';

if (! file_exists("$localVendor/bin")) {
    mkdir("$localVendor");
    symlink("$monorepoVendor/bin", "$localVendor/bin");
}

require $monorepoVendor.'/autoload.php';

putenv('FLARUM_TEST_VENDOR_PATH='.$monorepoVendor);

return new Flarum\Testing\integration\Setup\SetupScript();
