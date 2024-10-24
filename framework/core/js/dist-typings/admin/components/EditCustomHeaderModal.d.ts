/// <reference types="mithril" />
import SettingsModal from './SettingsModal';
export default class EditCustomHeaderModal extends SettingsModal {
    className(): string;
    title(): string | any[];
    form(): JSX.Element[];
    onsaved(): void;
}
