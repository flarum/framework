export default class BasicsPage extends AdminPage<import("../../common/components/Page").IPageAttrs> {
    constructor();
    localeOptions: {} | undefined;
    displayNameOptions: {} | undefined;
    slugDriverOptions: {} | undefined;
    /**
     * Build a list of options for the default homepage. Each option must be an
     * object with `path` and `label` properties.
     *
     * @return {ItemList<{ path: string, label: import('mithril').Children }>}
     */
    homePageItems(): ItemList<{
        path: string;
        label: import('mithril').Children;
    }>;
}
import AdminPage from "./AdminPage";
import ItemList from "../../common/utils/ItemList";
