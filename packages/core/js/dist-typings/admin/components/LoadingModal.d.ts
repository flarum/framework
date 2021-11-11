import Modal from '../../common/components/Modal';
export default class LoadingModal<ModalAttrs = {}> extends Modal<ModalAttrs> {
    /**
     * @inheritdoc
     */
    static readonly isDismissible: boolean;
    className(): string;
    title(): any;
    content(): string;
    onsubmit(e: Event): void;
}
