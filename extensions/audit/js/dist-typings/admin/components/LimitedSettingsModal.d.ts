/// <reference types="mithril" />
import SettingsModal from 'flarum/admin/components/SettingsModal';
export default class LimitedSettingsModal extends SettingsModal {
    className(): string;
    title(): string | any[];
    form(): (JSX.Element | JSX.Element[] | ((JSX.Element | JSX.Element[])[] | null)[])[];
}
