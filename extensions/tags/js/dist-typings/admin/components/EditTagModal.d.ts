import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import type Tag from '../../common/models/Tag';
export interface EditTagModalAttrs extends IInternalModalAttrs {
    primary?: boolean;
    model?: Tag;
}
/**
 * The `EditTagModal` component shows a modal dialog which allows the user
 * to create or edit a tag.
 */
export default class EditTagModal extends Modal<EditTagModalAttrs> {
    tag: Tag;
    name: Stream<string>;
    slug: Stream<string>;
    description: Stream<string>;
    color: Stream<string>;
    icon: Stream<string>;
    isHidden: Stream<boolean>;
    primary: Stream<boolean>;
    oninit(vnode: Mithril.Vnode<EditTagModalAttrs, this>): void;
    className(): string;
    title(): any;
    content(): JSX.Element;
    fields(): any;
    submitData(): {
        name: any;
        slug: any;
        description: any;
        color: any;
        icon: any;
        isHidden: any;
        primary: any;
    };
    onsubmit(e: SubmitEvent): void;
    delete(): void;
}
