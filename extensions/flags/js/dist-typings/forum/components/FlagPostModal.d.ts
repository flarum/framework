export default class FlagPostModal extends FormModal<import("flarum/common/components/FormModal").IFormModalAttrs, undefined> {
    constructor();
    oninit(vnode: any): void;
    success: boolean | undefined;
    reason: Stream<string> | undefined;
    reasonDetail: Stream<string> | undefined;
    title(): string | any[];
    content(): JSX.Element;
    flagReasons(): ItemList<any>;
    onsubmit(e: any): void;
}
import FormModal from "flarum/common/components/FormModal";
import Stream from "flarum/common/utils/Stream";
import ItemList from "flarum/common/utils/ItemList";
