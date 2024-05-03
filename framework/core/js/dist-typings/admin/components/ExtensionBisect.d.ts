import Modal, { IDismissibleOptions, type IInternalModalAttrs } from '../../common/components/Modal';
import Mithril from 'mithril';
export default class ExtensionBisect<CustomAttrs extends IInternalModalAttrs = IInternalModalAttrs> extends Modal<CustomAttrs> {
    private result;
    private bisecting;
    protected static readonly isDismissibleViaCloseButton: boolean;
    protected static readonly isDismissibleViaEscKey: boolean;
    protected static readonly isDismissibleViaBackdropClick: boolean;
    protected get dismissibleOptions(): IDismissibleOptions;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    stepsLeft(): number;
    submit(issue: boolean | null, end?: boolean): void;
    hide(extension?: string): void;
}
