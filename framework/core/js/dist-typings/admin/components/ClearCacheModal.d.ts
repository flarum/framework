import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import type Mithril from 'mithril';
/**
 * One thing the server did, as it reported it.
 *
 * Every step says what happened rather than describing it, so the wording here
 * comes from the translations — a sentence assembled on the server could only
 * ever have been in English.
 */
interface Step {
    type: 'cleared' | 'rebuilt' | 'failed' | 'done';
    name?: string;
    files?: number | null;
    frontend?: string;
    locale?: string | null;
    localeName?: string | null;
    bundle?: string;
    state?: 'unchanged' | 'rebuilt' | 'rewritten' | 'empty' | 'chunks';
    changed?: number | null;
    milliseconds?: number;
    step?: string;
    message?: string;
}
export interface IClearCacheModalAttrs extends IInternalModalAttrs {
}
/**
 * Clears the cache, showing each step as the server finishes it.
 *
 * The work recompiles every asset bundle for every frontend and locale, which
 * runs for tens of seconds where the assets live on remote storage. A spinner
 * alone gives no way to tell that from a request that has died, so the steps
 * are read from the response as they arrive.
 *
 * Nothing reloads on its own at the end: the page is still running on the
 * assets it booted with, and an admin who has just watched a rebuild should
 * decide for themselves when to pick up the new ones.
 */
export default class ClearCacheModal<ModalAttrs extends IClearCacheModalAttrs = IClearCacheModalAttrs> extends Modal<ModalAttrs> {
    protected steps: Step[];
    protected finished: boolean;
    protected static readonly isDismissibleViaCloseButton: boolean;
    protected static readonly isDismissibleViaEscKey: boolean;
    protected static readonly isDismissibleViaBackdropClick: boolean;
    oninit(vnode: Mithril.Vnode<ModalAttrs, this>): void;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
    /**
     * How much of the reader's attention a step deserves.
     *
     * Most of a clear is bundles that turned out not to need rebuilding, and on a
     * forum with several locales those rows are the majority. They are worth
     * listing — they are how you know nothing was missed — but not worth reading,
     * so they recede and the handful of things that actually changed stand out.
     */
    protected appearance(step: Step): string;
    /**
     * How long a step took, where that is worth knowing.
     *
     * Bundles compiled together are timed together, so the figure belongs to the
     * frontend rather than to each of its bundles — repeating it down every row
     * of a group would read as several slow steps instead of one. Shown against
     * the first row of each group only, as the console table does it.
     */
    protected time(step: Step, previous?: Step): Mithril.Children;
    protected icon(step: Step): string;
    /**
     * Puts words to a step. Which words depend only on what the server said
     * happened, never on a string it sent.
     */
    protected describe(step: Step): Mithril.Children;
    /**
     * Reads the response as it is written.
     *
     * `app.request` cannot be used here: it hands back the body once, complete,
     * so nothing could be shown until the work had finished. Each line is a whole
     * JSON object, so a step can be rendered the moment its line arrives.
     */
    protected clear(): Promise<void>;
}
export {};
