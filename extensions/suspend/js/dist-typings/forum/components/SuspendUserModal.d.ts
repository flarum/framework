export default class SuspendUserModal extends FormModal<import("flarum/common/components/FormModal").IFormModalAttrs, undefined> {
    constructor();
    oninit(vnode: any): void;
    status: Stream<string | null> | undefined;
    reason: Stream<any> | undefined;
    message: Stream<any> | undefined;
    daysRemaining: Stream<number | false> | undefined;
    title(): any[];
    content(): JSX.Element;
    radioItems(): ItemList<any>;
    formItems(): ItemList<any>;
    onsubmit(e: any): void;
}
import FormModal from "flarum/common/components/FormModal";
import Stream from "flarum/common/utils/Stream";
import ItemList from "flarum/common/utils/ItemList";
