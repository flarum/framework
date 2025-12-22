import FormModal from 'flarum/common/components/FormModal';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
export default class RequestErasureModal extends FormModal {
    reason: Stream<string>;
    password: Stream<string>;
    user: User | null;
    oninit(vnode: Mithril.Vnode<any, this>): void;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
    fields(): ItemList<Mithril.Children>;
    oncancel(e: Event): void;
    data(): {
        reason: string;
    };
    onsubmit(e: Event): void;
}
