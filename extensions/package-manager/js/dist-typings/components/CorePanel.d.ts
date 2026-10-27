import type { Preflight, Verdict } from '../types';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
interface CoreAttrs extends ComponentAttrs {
    starting: boolean;
    /** When the page's update check last ran; a new one means this panel asks again. */
    checkedAt: number | null;
    onbegin: (packages: string[]) => void;
    onnightly: () => void;
}
/**
 * Flarum itself.
 *
 * Updating core is the one operation whose blast radius is the whole forum,
 * so the question "what would this do to my extensions" is answered before
 * anything is pressed, per extension, by name. Composer's answer to the same
 * question is a refusal at the end of a long wait, written in package
 * constraints, from which somebody has to work out which of their thirty
 * extensions is the problem.
 *
 * The pre-flight arrives in two parts and this is not an optimisation. The
 * cheap half — does the version you ALREADY have declare support — is in
 * composer.lock and is instant for any number of extensions. The rest costs one
 * HTTP call each: 58 of them took 28 seconds in a single request when this was
 * first built, which is the very failure the extension exists to prevent.
 */
export default class CorePanel extends Component<CoreAttrs> {
    protected loading: boolean;
    protected current: string | null;
    protected newest: {
        from: string;
        to: string;
    } | null;
    protected preflight: Preflight | null;
    protected checking: boolean;
    protected failed: boolean;
    protected seenCheck: number | null;
    oninit(vnode: Mithril.Vnode<CoreAttrs, this>): void;
    onupdate(): void;
    view(): JSX.Element;
    confirmNightly(): void;
    summary(): JSX.Element | null;
    rows(): JSX.Element | null;
    blocked(): number;
    /**
     * Core AND every extension that needs to move, resolved together in one
     * request. Updating core alone would leave Composer refusing, because the
     * extensions still on disk pin the old version — and updating them one at a
     * time afterwards means a forum that is broken in between.
     */
    packagesToUpdate(): string[];
    load(): void;
    runPreflight(target: string): void;
    /** One batch at a time, so no single request can cause a burst of outbound calls. */
    resolve(pending: string[], target: string): void;
    applyVerdicts(verdicts: Record<string, Verdict>): void;
}
export {};
