import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import type Mithril from 'mithril';
export default class FontAwesomePreviewModal extends Modal<IInternalModalAttrs> {
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
}
