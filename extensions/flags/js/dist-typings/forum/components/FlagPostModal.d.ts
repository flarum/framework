export default class FlagPostModal {
    oninit(vnode: any): void;
    success: boolean | undefined;
    reason: any;
    reasonDetail: any;
    className(): string;
    title(): any;
    content(): JSX.Element;
    flagReasons(): any;
    onsubmit(e: any): void;
    loading: boolean | undefined;
}
