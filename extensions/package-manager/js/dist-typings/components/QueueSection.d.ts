import type Mithril from 'mithril';
import Component from 'flarum/common/Component';
import { TaskOperations } from '../models/Task';
export default class QueueSection extends Component<{}> {
    oninit(vnode: Mithril.Vnode<{}, this>): void;
    view(): JSX.Element;
    columns(): any;
    queueTable(): JSX.Element;
    operationIcon(operation: TaskOperations): Mithril.Children;
}
