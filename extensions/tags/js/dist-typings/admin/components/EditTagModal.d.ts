import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import type Tag from '../../common/models/Tag';
export interface EditTagModalAttrs extends IFormModalAttrs {
    primary?: boolean;
    model?: Tag;
}
/**
 * The `EditTagModal` component shows a modal dialog which allows the user
 * to create or edit a tag.
 */
export default class EditTagModal extends FormModal<EditTagModalAttrs> {
    tag: Tag;
    name: Stream<string>;
    slug: Stream<string>;
    description: Stream<string>;
    color: Stream<string>;
    icon: Stream<string>;
    isHidden: Stream<boolean>;
    isPrimary: Stream<boolean>;
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
        isPrimary: any;
    };
    onsubmit(e: SubmitEvent): void;
    delete(): void;
}
