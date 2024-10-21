import FormModal, { type IFormModalAttrs } from './FormModal';
import type User from '../models/User';
import Mithril from 'mithril';
import Stream from '../utils/Stream';
import { throttle } from '../utils/throttleDebounce';
export interface IUserSelectionModalAttrs extends IFormModalAttrs {
    title?: string;
    selected: User[];
    onsubmit: (users: User[]) => void;
    maxItems?: number;
    excluded?: (number | string)[];
}
/**
 * The `UserSelectionModal` component displays a modal dialog with searchable
 * user list and submit button. The user can select one or more users from the
 * list and submit them to the callback.
 */
export default class UserSelectionModal<CustomAttrs extends IUserSelectionModalAttrs = IUserSelectionModalAttrs> extends FormModal<CustomAttrs> {
    protected search: Stream<string>;
    protected selected: Stream<User[]>;
    protected focused: boolean;
    protected results: Stream<Record<string, User[] | null>>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    userListItem(user: User): JSX.Element;
    meetsRequirements(): boolean;
    onsubmit(e: SubmitEvent): void;
    protected load: throttle<() => Promise<void> | undefined>;
}
