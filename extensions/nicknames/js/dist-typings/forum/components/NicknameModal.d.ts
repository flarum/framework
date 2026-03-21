export default class NicknameModal extends FormModal<import("flarum/common/components/FormModal").IFormModalAttrs, undefined> {
    constructor();
    oninit(vnode: any): void;
    nickname: Stream<string> | undefined;
    title(): string | any[];
    content(): JSX.Element;
    onsubmit(e: any): void;
}
import FormModal from "flarum/common/components/FormModal";
import Stream from "flarum/common/utils/Stream";
