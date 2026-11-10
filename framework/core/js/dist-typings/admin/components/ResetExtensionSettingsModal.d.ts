import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import type Mithril from 'mithril';
export interface ResetSettingItem {
    key: string;
    label?: Mithril.Children;
}
export interface IResetExtensionSettingsModalAttrs extends IInternalModalAttrs {
    settings: ResetSettingItem[];
    extensionId?: string;
    title?: string;
}
export default class ResetExtensionSettingsModal<CustomAttrs extends IResetExtensionSettingsModalAttrs = IResetExtensionSettingsModalAttrs> extends Modal<CustomAttrs> {
    protected loading: boolean;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
    confirm(): Promise<void>;
}
