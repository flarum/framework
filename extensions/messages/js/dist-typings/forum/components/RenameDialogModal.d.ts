import FormModal, { type IFormModalAttrs } from 'flarum/common/components/FormModal';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import type Dialog from '../../common/models/Dialog';
export interface IRenameDialogModalAttrs extends IFormModalAttrs {
    dialog: Dialog;
    /** Once the name is saved, and the group has been told. */
    onsave?: () => void;
}
/**
 * Names a group, or takes its name away so its members title it again.
 */
export default class RenameDialogModal<CustomAttrs extends IRenameDialogModalAttrs = IRenameDialogModalAttrs> extends FormModal<CustomAttrs> {
    protected name: Stream<string>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
    onsubmit(e: SubmitEvent): Promise<void> | void;
}
