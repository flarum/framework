import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Stream from '../../common/utils/Stream';
import Mithril from 'mithril';
import { MutableSettings, SettingValue } from './AdminPage';
export interface ISettingsModalAttrs extends IInternalModalAttrs {
}
export default abstract class SettingsModal<CustomAttrs extends ISettingsModalAttrs = ISettingsModalAttrs> extends Modal<CustomAttrs> {
    settings: MutableSettings;
    loading: boolean;
    form(): Mithril.Children;
    content(): JSX.Element;
    submitButton(): Mithril.Children;
    setting(key: string, fallback?: string): Stream<SettingValue>;
    dirty(): Record<string, string>;
    changed(): number;
    onsubmit(e: SubmitEvent): void;
    onsaved(): void;
}
