import AdminPage from './AdminPage';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
export default class AppearancePage extends AdminPage {
    headerInfo(): {
        className: string;
        icon: string;
        title: string | any[];
        description: string | any[];
    };
    content(): JSX.Element;
    colorItems(): ItemList<Mithril.Children>;
    onsaved(): void;
    static register(): void;
}
