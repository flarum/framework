import Component from 'flarum/common/Component';
import type Mithril from 'mithril';
type CheckState = 'ok' | 'warning' | 'pending';
/**
 * What the server needs for AI features to work well, checked where it can be:
 * whether streamed replies reach the browser as they're written, whether
 * background work has a queue to run on, and whether the scheduler runs the
 * daily checks.
 */
export default class ServerChecks extends Component {
    oncreate(vnode: Mithril.VnodeDOM<{}, this>): void;
    view(): Mithril.Children;
    check(key: string, state: CheckState, label: string, text: Mithril.Children, control?: Mithril.Children): Mithril.Children;
    streamingCheck(): Mithril.Children;
    queueCheck(): Mithril.Children;
    schedulerCheck(): Mithril.Children;
}
export {};
