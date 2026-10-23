import type { Found, Verdict } from '../types';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
interface DiscoverAttrs extends ComponentAttrs {
    starting: boolean;
    oninstall: (packageName: string) => void;
}
/**
 * Finding something to install.
 *
 * The one thing this does that a bare Packagist search does not: it says
 * whether a result works with the Flarum you actually have, BEFORE you press
 * anything — not at install time, after the resolve, after the wait, and in the
 * form of a constraint error rather than a sentence.
 *
 * Verdicts arrive in a second request, so results appear immediately and the
 * badges fill in. Waiting on a dozen round trips before showing anything would
 * be the slower, worse version of the same screen.
 */
export default class DiscoverTab extends Component<DiscoverAttrs> {
    oninit(vnode: Mithril.Vnode<DiscoverAttrs, this>): void;
    private query;
    /** '' is everything; the rest are the keys of Packagist::CATEGORIES. */
    private category;
    private searching;
    private searched;
    private page;
    private more;
    private loadingMore;
    private results;
    private verdicts;
    private installedNames;
    private checking;
    private error;
    private core;
    private seq;
    /** The query whose results are on screen, so a keystroke can tell if anything changed. */
    private lastSearched;
    /** The pending debounce, so a new keystroke replaces it rather than adding to it. */
    private typing;
    view(): JSX.Element;
    /**
     * A keystroke, considered.
     *
     * The rule lives in searchPolicy so it can be tested; this is only the timer.
     */
    queueSearch(): void;
    stopTyping(): void;
    onremove(): void;
    card(r: Found): JSX.Element;
    /**
     * Four states, and "we don't know yet" is one of them. Collapsing unknown
     * into "no" hides working extensions; collapsing it into "yes" offers ones
     * that cannot install. Either way somebody stops believing the badge.
     */
    badge(v?: Verdict): JSX.Element;
    /**
     * Packagist records a replacement package when the author names one, and that
     * is the single most useful thing to say about an abandoned extension.
     */
    abandonedTitle(r: Found): string;
    /**
     * The package to use instead of this one, if any: the one Packagist names for
     * an abandoned package, or the new name of a repository that moved.
     */
    replacement(r: Found, v?: Verdict): string | null;
    action(r: Found, v?: Verdict): JSX.Element | null;
    short(n: number): string;
    search(): void;
    /**
     * The next page, appended.
     *
     * Appended rather than replacing, because browsing is a scroll and losing
     * what you already looked at to see more of it is the wrong trade. Each page
     * costs its own compatibility pass, which is why they are fetched a page at a
     * time rather than all 2306 at once.
     */
    showMore(): void;
    checkCompat(names: string[], mine: number): void;
}
export {};
