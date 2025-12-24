/// <reference types="mithril" />
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import ItemList from 'flarum/common/utils/ItemList';
import type ErasureRequestsListState from '../states/ErasureRequestsListState';
import type ErasureRequest from '../../common/models/ErasureRequest';
export interface IErasureRequestsListAttrs extends ComponentAttrs {
    state: ErasureRequestsListState;
}
export default class ErasureRequestsList<CustomAttrs extends IErasureRequestsListAttrs = IErasureRequestsListAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
    showModal(request: ErasureRequest): void;
    controlItems(): ItemList<unknown>;
    content(state: ErasureRequestsListState): JSX.Element[][] | null;
}
