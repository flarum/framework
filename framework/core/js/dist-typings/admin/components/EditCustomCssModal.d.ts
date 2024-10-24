/// <reference types="mithril" />
import SettingsModal from './SettingsModal';
export default class EditCustomCssModal extends SettingsModal {
    className(): string;
    title(): string | any[];
    form(): JSX.Element[];
    onsaved(): void;
}
