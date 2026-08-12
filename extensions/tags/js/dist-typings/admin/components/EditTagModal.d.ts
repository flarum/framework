import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import ItemList from 'flarum/common/utils/ItemList';
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
    defaultSort: Stream<string>;
    oninit(vnode: Mithril.Vnode<EditTagModalAttrs, this>): void;
    className(): string;
    title(): string | any[] | Mithril.Vnode<import("flarum/common/Component").ComponentAttrs, any>;
    content(): JSX.Element;
    fields(): ItemList<unknown>;
    /**
     * The sorts a discussion list can be ordered by, as the forum offers them.
     *
     * Read from the payload rather than listed here, so that a sort added by an
     * extension can be chosen as a tag's default without that extension knowing
     * anything about tags.
     */
    sortOptions(): Record<string, string>;
    defaultSortField(): JSX.Element;
    submitData(): {
        name: string;
        slug: string;
        description: string;
        color: string;
        icon: string;
        isHidden: boolean;
        isPrimary: boolean;
        defaultSort: string | null;
    };
    onsubmit(e: SubmitEvent): void;
    delete(): void;
}
