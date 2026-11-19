import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
interface Pending {
    package: string;
    name: string;
    from: string | null;
    to: string | null;
}
/**
 * Tells an admin, on the page they already land on, that something is out of
 * date — without overstating what that means.
 *
 * The wording matters more than the banner. "3 updates available" is a claim
 * this cannot back: the cheap Packagist check knows a newer version EXISTS, not
 * that it will install alongside everything else on the forum. So it says a
 * newer version exists and offers to go and find out properly, which is a
 * sentence that stays true.
 *
 * It also carries its own age. A badge with no timestamp is indistinguishable
 * from a stale one, and a stale badge is how people learn to ignore badges.
 */
export default class UpdateBanner extends Component {
    loading: boolean;
    count: number;
    /**
     * WHICH packages, not just how many.
     *
     * The banner used to say "1 package has a newer version" and stop there, so
     * the only way to find out which one was to open the page and read a grid of
     * every extension on the forum looking for a tag. A notification that does
     * not name its subject makes the reader do the work it exists to save.
     */
    pending: Pending[];
    checkedAt: number | null;
    stale: boolean;
    uncheckable: number;
    dismissed: boolean;
    oninit(vnode: Mithril.Vnode<ComponentAttrs, this>): void;
    view(): JSX.Element | null;
    /**
     * The extensions themselves, with the versions involved.
     *
     * Capped, and honest about the cap. A forum with thirty out-of-date
     * packages would otherwise turn a banner into a wall, and the reader who has
     * thirty does not need them enumerated — they need to open the page.
     */
    names(): JSX.Element;
    /**
     * Dismissal is remembered against WHICH packages were out of date, not as a
     * flag. Dismissing "3 updates" and then never hearing about a fourth is how a
     * notification quietly stops working.
     */
    protected key(packages: string[]): string;
    protected wasDismissed(packages: string[]): boolean;
    protected dismiss(): void;
    protected ago(unix: number): string;
}
export {};
