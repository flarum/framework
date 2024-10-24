import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
import type { Children } from 'mithril';
export default class DashboardPage extends AdminPage {
    headerInfo(): {
        className: string;
        icon: string;
        title: string | any[];
        description: string | any[];
    };
    content(): (Children & {
        itemName: string;
    })[];
    availableWidgets(): ItemList<Children>;
}
