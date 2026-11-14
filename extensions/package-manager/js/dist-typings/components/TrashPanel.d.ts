import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import type { TrashPayload } from '../types';
/**
 * The rollback copies: how much room they take, how long they are kept, and a
 * button to tidy them now.
 *
 * The confirm quotes numbers the SERVER just worked out with the same rule
 * the prune will use, and the prune recomputes rather than taking a list from
 * here. The browser asks whether to prune, never what — so what somebody agrees
 * to and what happens cannot drift apart.
 */
export default class TrashPanel extends Component {
    protected loading: boolean;
    protected busy: boolean;
    protected data: TrashPayload | null;
    /** Why the copies could not be read at all, when they could not. */
    protected loadError: string | null;
    protected keepDays: string;
    protected keepRuns: string;
    oninit(vnode: Mithril.Vnode<ComponentAttrs, this>): void;
    view(): JSX.Element;
    confirmPrune(d: TrashPayload): void;
    load(): void;
    /** A change: its outcome is announced through Flarum's alerts, and a refusal in the server's own words. */
    send(body: Record<string, string>, doneText?: string): void;
    protected show(data: TrashPayload): void;
}
