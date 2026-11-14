import Modal, { type IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';
import { ConnectionTestState } from './ConnectionTest';
export interface ConnectionTestModalAttrs extends IInternalModalAttrs {
    /** A saved connection, or one set in config.php. */
    name: string;
}
/**
 * Tests a saved connection from the list on the Connections tab.
 */
export default class ConnectionTestModal<CustomAttrs extends ConnectionTestModalAttrs = ConnectionTestModalAttrs> extends Modal<CustomAttrs> {
    test: ConnectionTestState;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): any[];
    content(): JSX.Element;
}
