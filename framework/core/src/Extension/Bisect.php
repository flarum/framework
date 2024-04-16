<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Closure;
use Composer\Command\ClearCacheCommand;
use Flarum\Settings\SettingsRepositoryInterface;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class Bisect
{
    protected BisectState $state;

    /**
     * When running bisect across multiple processes (such as multiple HTTP requests),
     * this flag can be used to stop the bisect process after the first step it completes.
     */
    protected bool $break = false;

    protected bool $issueChecked = false;
    protected ?Closure $isIssuePresent = null;

    public function __construct(
        protected ExtensionManager $extensions,
        protected SettingsRepositoryInterface $settings,
        protected ClearCacheCommand $clearCache,
    ) {
        $this->state = BisectState::continueOrStart(
            $ids = $this->extensions->getEnabled(),
            0,
            count($ids) - 1
        );
    }

    public function break(bool $break = true): self
    {
        $this->break = $break;

        return $this;
    }

    public function checkIssueUsing(Closure $isIssuePresent): self
    {
        $this->isIssuePresent = $isIssuePresent;

        return $this;
    }

    /**
     * @return array{
     *     'stepsLeft': int,
     *     'relevantEnabled': string[],
     *     'relevantDisabled': string[],
     *     'extension': ?string,
     * }|null
     */
    public function run(): ?array
    {
        if (is_null($this->isIssuePresent)) {
            throw new RuntimeException('You must provide a closure to check if the issue is present.');
        }

        $this->settings->set('maintenance_mode', 'low');

        return $this->bisect($this->state);
    }

    protected function bisect(BisectState $state): ?array
    {
        [$ids, $low, $high] = [$state->ids, $state->low, $state->high];

        if ($low > $high) {
            $this->end();

            return null;
        }

        $mid = (int) (($low + $high) / 2);
        $enabled = array_slice($ids, 0, $mid + 1);

        $relevantEnabled = array_slice($ids, $low, $mid - $low + 1);
        $relevantDisabled = array_slice($ids, $mid + 1, $high - $mid);
        $stepsLeft = round(log($high - $low + 1, 2));

        $this->rotateExtensions($enabled);

        $current = [
            'stepsLeft' => $stepsLeft,
            'relevantEnabled' => $relevantEnabled,
            'relevantDisabled' => $relevantDisabled,
            'extension' => null,
        ];

        if (! $this->break || ! $this->issueChecked) {
            $issue = ($this->isIssuePresent)($current);
            $this->issueChecked = true;
        } else {
            return $current;
        }

        if (count($relevantEnabled) === 1 && $issue) {
            return $this->foundIssue($relevantEnabled[0]);
        }

        if (count($relevantDisabled) === 1 && ! $issue) {
            return $this->foundIssue($relevantDisabled[0]);
        }

        if ($issue) {
            return $this->bisect($this->state->advance($low, $mid));
        } else {
            return $this->bisect($this->state->advance($mid + 1, $high));
        }
    }

    protected function foundIssue(string $id): array
    {
        $this->end();

        return [
            'stepsLeft' => 0,
            'relevantEnabled' => [],
            'relevantDisabled' => [],
            'extension' => $id,
        ];
    }

    public function end(): void
    {
        $this->settings->set('extensions_enabled', json_encode($this->state->ids));
        $this->settings->set('maintenance_mode', 'none');
        $this->state->end();
        $this->clearCache->run(new ArrayInput([]), new NullOutput());
    }

    protected function rotateExtensions(array $enabled): void
    {
        $this->settings->set('extensions_enabled', json_encode($enabled));
        $this->clearCache->run(new ArrayInput([]), new NullOutput());
    }
}
