import AdminPage, { AdminHeaderAttrs } from 'flarum/admin/components/AdminPage';
import type { IPageAttrs } from 'flarum/common/components/Page';
import type Mithril from 'mithril';
import DataType from '../models/DataType';
type ColumnMeta = {
    type: string;
    length: number | null;
    default: string | null;
    nullable: boolean;
};
type UserColumnsData = {
    allColumns: Record<string, ColumnMeta>;
    removableColumns: Record<string, string | null>;
    piiKeys: string[];
    piiKeyExtensions: Record<string, string | null>;
};
export default class GdprPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends AdminPage<CustomAttrs> {
    gdprDataTypes: DataType[];
    userColumnsData: UserColumnsData | null;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    headerInfo(): AdminHeaderAttrs;
    loadGdprDataTypes(): void;
    loadUserColumnsData(): void;
    content(): Mithril.Children;
    grid(): JSX.Element;
    userColumnTable({ allColumns, removableColumns, piiKeys, piiKeyExtensions }: UserColumnsData): Mithril.Children;
}
export {};
