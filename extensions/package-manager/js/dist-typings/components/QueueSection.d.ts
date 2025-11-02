import type Mithril from 'mithril';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import ItemList from 'flarum/common/utils/ItemList';
import Task, { TaskOperations } from '../models/Task';
interface QueueTableColumn extends ComponentAttrs {
    label: string;
    content: (task: Task) => Mithril.Children;
}
export default class QueueSection extends Component<{}> {
    oninit(vnode: Mithril.Vnode<{}, this>): void;
    view(): JSX.Element;
    columns(): ItemList<QueueTableColumn>;
    queueTable(): JSX.Element;
    operationIcon(operation: TaskOperations): Mithril.Children;
}
export {};
