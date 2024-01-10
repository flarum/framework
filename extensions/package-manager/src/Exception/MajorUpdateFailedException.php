<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Exception;

use Composer\Semver\Semver;
use Flarum\ExtensionManager\Event\FlarumUpdated;
use Flarum\ExtensionManager\Settings\LastUpdateRun;

class MajorUpdateFailedException extends ComposerCommandFailedException
{
    private const INCOMPATIBLE_REGEX = '/^ +- (?<ext>[A-z0-9\/-]+) [A-z0-9.-_\/]+ requires flarum\/core (?<coreReq>(?:[A-z0-9.><=_ -](?!->))+)/m';

    /**
     * @var string
     */
    private $majorVersion;

    public function __construct(string $packageName, string $output, string $majorVersion)
    {
        $this->majorVersion = $majorVersion;

        parent::__construct($packageName, $output);
    }

    public function guessCause(): ?string
    {
        if (preg_match_all(self::INCOMPATIBLE_REGEX, $this->getMessage(), $matches) !== false) {
            $this->details['incompatible_extensions'] = [];

            foreach ($matches['ext'] as $k => $name) {
                if (! Semver::satisfies($this->majorVersion, $matches['coreReq'][$k])) {
                    $this->details['incompatible_extensions'][] = $name;
                }
            }

            resolve(LastUpdateRun::class)
                ->for(FlarumUpdated::MAJOR)
                ->with('status', LastUpdateRun::FAILURE)
                ->with('incompatibleExtensions', $this->details['incompatible_extensions'])
                ->save();

            return 'extensions_incompatible_with_new_major';
        }

        return null;
    }
}
