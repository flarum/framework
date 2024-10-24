import AdminPage from './AdminPage';
import type { IPageAttrs } from '../../common/components/Page';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
export default class AdvancedPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends AdminPage<CustomAttrs> {
    searchDriverOptions: Record<string, Record<string, string>>;
    urlRequestedModalHasBeenShown: boolean;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    headerInfo(): {
        className: string;
        icon: string;
        title: string | any[];
        description: string | any[];
    };
    content(): JSX.Element[];
    driverLocale(): Record<string, Record<string, string>>;
    sectionItems(): ItemList<Mithril.Children>;
    searchDrivers(): JSX.Element;
    maintenance(): JSX.Element;
    pgsqlSettings(): JSX.Element;
    static register(): void;
}
