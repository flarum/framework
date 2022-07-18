/// <reference types="mithril" />
/// <reference types="flarum/@types/translator-icu-rich" />
import SettingsModal from './SettingsModal';
export default class EditCustomCssModal extends SettingsModal {
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    form(): JSX.Element[];
    onsaved(): void;
}
