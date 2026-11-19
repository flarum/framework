import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import OAuthClient from '../models/OAuthClient';
interface ClientFormModalAttrs extends IFormModalAttrs {
    client?: OAuthClient;
    onSaved: (client: OAuthClient, plainSecret: string | null) => void;
}
export default class ClientFormModal extends FormModal<ClientFormModalAttrs> {
    name: Stream<string>;
    redirectUris: Stream<string>;
    scopes: Stream<string>;
    confidential: Stream<boolean>;
    revoked: Stream<boolean>;
    oninit(vnode: Mithril.Vnode<ClientFormModalAttrs, this>): void;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
    onsubmit(e: SubmitEvent): void;
}
export {};
