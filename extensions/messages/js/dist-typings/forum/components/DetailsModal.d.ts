/// <reference types="mithril" />
import Modal, { type IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Dialog from '../../common/models/Dialog';
export interface IDetailsModalAttrs extends IInternalModalAttrs {
    dialog: Dialog;
}
export default class DetailsModal<CustomAttrs extends IDetailsModalAttrs = IDetailsModalAttrs> extends Modal<CustomAttrs> {
    className(): string;
    title(): any;
    content(): JSX.Element;
    infoItems(): any;
}
