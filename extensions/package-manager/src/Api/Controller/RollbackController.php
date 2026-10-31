<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Controller;

use Flarum\ExtensionManager\Apply\Journal;
use Flarum\ExtensionManager\Apply\Restore;
use Flarum\ExtensionManager\Exception\RefusedException;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Work\ComposerRunner;
use Flarum\ExtensionManager\Work\WorkDir;
use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Flarum\Locale\TranslatorInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Put everything back the way it was.
 *
 * Works on a finished run AND on one that died halfway. That is the whole
 * claim: the journal records what was about to happen before it happened, so an
 * interrupted update is as recoverable as a completed one. There is no state
 * this refuses to unwind.
 */
class RollbackController implements RequestHandlerInterface
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected RunStore $runs,
        protected Paths $paths,
    ) {
    }

    protected function composer(): ComposerRunner
    {
        return new ComposerRunner(
            $this->translator,
            $this->paths->base,
            null,
            $this->paths->storage.'/.composer',
            (new \Flarum\ExtensionManager\Host\PhpOverride($this->translator, $this->paths->storage))->get()
        );
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $run = $this->runs->latest();

        if ($run === null) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.rollback.nothing'), 'nothing_to_roll_back', 404);
        }

        $workDir = new WorkDir($this->translator, $this->paths->storage, $run->id);
        $journal = new Journal($this->translator, $workDir->journalPath());

        /*
         * An empty journal does NOT mean nothing changed.
         *
         * The plan phase runs Composer with --no-install, and Composer rewrites
         * composer.json and composer.lock the moment it succeeds — before a
         * single file has been moved, so before the journal has anything in it.
         * A run that then fails leaves the manifests describing a site that does
         * not exist, and the version of this that refused on an empty journal
         * said "that update never got as far as changing anything", which was
         * simply false. Found by a `composer remove` that updated both files and
         * then exited non-zero.
         *
         * So the question is not "is there a journal" but "is there anything
         * saved to put back".
         */
        $savedLock = $workDir->root().'/composer.lock.before';

        if (! $journal->exists() && ! is_file($savedLock)) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.rollback.nothing_changed'), 'nothing_to_roll_back');
        }

        /*
         * The same Restore the run uses when it undoes itself. Two copies of
         * "put it back" — one for the button, one for the automatic path — is
         * exactly the pair that drifts, and the half that drifts is Composer's
         * record, which fails silently and months later.
         */
        $restore = new Restore(
            $this->translator,
            $this->paths->vendor,
            $this->paths->base,
            $workDir->root(),
            $workDir->trash(),
            $journal,
            $this->composer(),
            $this->paths->storage
        );

        // A refusal (a database change that cannot be reversed) changes nothing
        // and reaches the admin as a RefusedException.
        $result = $restore->run();

        $undone = $result['undone'];
        $note = $result['note'];

        $this->runs->save($run->rolledBack(time(), $this->translator->trans('flarum-extension-manager.api.run.rolled_back', [
            'undone' => implode(', ', $undone) ?: $this->translator->trans('flarum-extension-manager.api.run.nothing_to_undo'),
        ])));

        return new JsonResponse([
            'undone' => $undone,
            'run' => $this->runs->present($this->runs->latest()),
            'next' => $note,
        ]);
    }
}
