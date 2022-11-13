/// <reference path="../../@types/translator-icu-rich.d.ts" />
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import ExtensionReadme from '../models/ExtensionReadme';
import type Mithril from 'mithril';
import type { Extension } from '../AdminApplication';
export interface IReadmeModalAttrs extends IInternalModalAttrs {
    extension: Extension;
}
export default class ReadmeModal<CustomAttrs extends IReadmeModalAttrs = IReadmeModalAttrs> extends Modal<CustomAttrs> {
    protected name: string;
    protected extName: string;
    protected readme: ExtensionReadme;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    loadReadme(): Promise<void>;
}
