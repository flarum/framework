import type { IButtonAttrs } from './Button';
import type Mithril from 'mithril';
import Component from '../Component';
export interface IUploadImageButtonAttrs extends IButtonAttrs {
    name: string;
    routePath: string;
    value?: string | null | (() => string | null);
    url?: string | null | (() => string | null);
}
export default class UploadImageButton<CustomAttrs extends IUploadImageButtonAttrs = IUploadImageButtonAttrs> extends Component<CustomAttrs> {
    loading: boolean;
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    upload(): void;
    remove(): void;
    resourceUrl(): string;
    /**
     * After a successful upload/removal, reload the page.
     */
    protected success(response: any): void;
    /**
     * If upload/removal fails, stop loading.
     */
    protected failure(response: any): void;
}
