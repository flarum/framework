/// <reference types="mithril" />
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
interface NewClientSecretModalAttrs extends IInternalModalAttrs {
    clientId: string;
    clientSecret: string;
    rotated?: boolean;
}
export default class NewClientSecretModal extends Modal<NewClientSecretModalAttrs> {
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
}
export {};
