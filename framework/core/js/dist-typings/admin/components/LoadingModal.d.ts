/// <reference path="../../../src/common/translator-icu-rich.d.ts" />
import Modal from '../../common/components/Modal';
export default class LoadingModal<ModalAttrs = {}> extends Modal<ModalAttrs> {
    /**
     * @inheritdoc
     */
    static readonly isDismissible: boolean;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): string;
    onsubmit(e: Event): void;
}
