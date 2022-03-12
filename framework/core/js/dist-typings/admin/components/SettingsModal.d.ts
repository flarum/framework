export default class SettingsModal extends Modal<import("../../common/components/Modal").IInternalModalAttrs> {
    constructor();
    oninit(vnode: any): void;
    settings: {} | undefined;
    form(): string;
    content(): JSX.Element;
    submitButton(): JSX.Element;
    setting(key: any, fallback?: string): any;
    dirty(): {};
    changed(): number;
    onsubmit(e: any): void;
    onsaved(): void;
}
import Modal from "../../common/components/Modal";
