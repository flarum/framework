import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import { type ModelLists } from '../utils/models';
export interface ModelInputAttrs extends ComponentAttrs {
    id: string;
    value: string;
    oninput: (value: string) => void;
    /** The connection whose models to offer, or null to offer none. */
    connection: string | null;
    lists: ModelLists;
    placeholder?: string;
    maxlength?: number;
    /** The model used when the field is empty, which is checked too. */
    defaultModel?: string | null;
}
/**
 * A model name, suggested from the connection's list, and still free to type,
 * so a model the provider doesn't list can be used. Says when the model is
 * deprecated or not offered.
 */
export default class ModelInput extends Component<ModelInputAttrs> {
    oncreate(vnode: Mithril.VnodeDOM<ModelInputAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<ModelInputAttrs, this>): void;
    load(): void;
    view(): JSX.Element;
}
