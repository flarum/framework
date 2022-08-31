/// <reference types="@flarum/core/dist-typings/@types/translator-icu-rich" />
import type Mithril from 'mithril';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
export interface WhyNotModalAttrs extends IInternalModalAttrs {
    package: string;
}
export default class WhyNotModal<CustomAttrs extends WhyNotModalAttrs = WhyNotModalAttrs> extends Modal<CustomAttrs> {
    loading: boolean;
    whyNot: string | null;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    content(): JSX.Element;
    requestWhyNot(): void;
}
