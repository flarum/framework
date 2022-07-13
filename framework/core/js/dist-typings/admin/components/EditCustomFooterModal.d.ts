/// <reference path="../../@types/translator-icu-rich.d.ts" />
/// <reference types="mithril" />
import SettingsModal from './SettingsModal';
export default class EditCustomFooterModal extends SettingsModal {
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    form(): JSX.Element[];
    onsaved(): void;
}
