import SettingsModal, { type ISettingsModalAttrs } from './SettingsModal';
import Mithril from 'mithril';
export default class EditCustomCssModal extends SettingsModal {
    oninit(vnode: Mithril.Vnode<ISettingsModalAttrs, this>): void;
    className(): string;
    title(): string | any[];
    form(): JSX.Element[];
    onsaved(): void;
}
