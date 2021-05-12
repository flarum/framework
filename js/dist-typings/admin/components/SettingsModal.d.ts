export default class SettingsModal extends Modal {
    settings: {} | undefined;
    form(): string;
    submitButton(): JSX.Element;
    setting(key: any, fallback?: string): any;
    dirty(): {};
    changed(): number;
    onsaved(): void;
}
import Modal from "../../common/components/Modal";
