/// <reference types="flarum/@types/translator-icu-rich" />
import Modal, { type IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Dialog from '../../common/models/Dialog';
import ItemList from 'flarum/common/utils/ItemList';
import Mithril from 'mithril';
export interface IDetailsModalAttrs extends IInternalModalAttrs {
    dialog: Dialog;
}
export default class DetailsModal<CustomAttrs extends IDetailsModalAttrs = IDetailsModalAttrs> extends Modal<CustomAttrs> {
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    infoItems(): ItemList<Mithril.Children>;
}
