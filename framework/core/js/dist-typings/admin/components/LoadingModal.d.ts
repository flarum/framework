/// <reference path="../../@types/translator-icu-rich.d.ts" />
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
export interface ILoadingModalAttrs extends IInternalModalAttrs {
}
export default class LoadingModal<ModalAttrs extends ILoadingModalAttrs = ILoadingModalAttrs> extends Modal<ModalAttrs> {
    /**
     * @inheritdoc
     */
    static readonly isDismissible: boolean;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): null;
    onsubmit(e: Event): void;
}
