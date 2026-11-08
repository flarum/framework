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
    content(): (Mithril.Children & {
        itemName: string;
    })[];
    contentItems(): ItemList<Mithril.Children>;
    brandingItems(): ItemList<Mithril.Children>;
    /**
     * The images a theme sets with the Theme extender, with the title of the
     * extension that set each one.
     */
    themeImages(): Record<string, string | null>;
    themeImage(name: string): {
        lockedBy?: string | null;
    };
    colorItems(): ItemList<Mithril.Children>;
    onsaved(): void;
    static register(): void;
}
