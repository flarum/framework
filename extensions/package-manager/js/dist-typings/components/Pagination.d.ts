/// <reference types="mithril" />
import Component, { ComponentAttrs } from 'flarum/common/Component';
import QueueState from '../states/QueueState';
interface PaginationAttrs extends ComponentAttrs {
    list: QueueState;
}
/**
 * @todo make it abstract in core for reusability.
 */
export default class Pagination extends Component<PaginationAttrs> {
    view(): JSX.Element;
}
export {};
