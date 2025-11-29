import AdminPage, { AdminHeaderAttrs } from 'flarum/admin/components/AdminPage';
import type { IPageAttrs } from 'flarum/common/components/Page';
import type Mithril from 'mithril';
import DataType from '../models/DataType';
export default class GdprPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends AdminPage<CustomAttrs> {
    gdprDataTypes: DataType[];
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    headerInfo(): AdminHeaderAttrs;
    loadGdprDataTypes(): void;
    content(): Mithril.Children;
    grid(): JSX.Element;
}
