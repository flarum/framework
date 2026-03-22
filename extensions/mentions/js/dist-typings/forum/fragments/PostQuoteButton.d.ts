export default class PostQuoteButton extends Fragment {
    constructor(post: any);
    post: any;
    view(): JSX.Element;
    show(left: any, top: any): void;
    hideHandler: (() => void) | undefined;
    showStart(left: any, top: any): void;
    showEnd(right: any, bottom: any): void;
    hide(): void;
}
import Fragment from "flarum/common/Fragment";
