/// <reference types="mithril" />
import Modal from 'flarum/common/components/Modal';
export default class AuditModal extends Modal {
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
}
