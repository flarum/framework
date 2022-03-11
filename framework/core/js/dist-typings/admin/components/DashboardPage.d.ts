export default class DashboardPage extends AdminPage<import("../../common/components/Page").IPageAttrs> {
    constructor();
    availableWidgets(): ItemList<any>;
}
import AdminPage from "./AdminPage";
import ItemList from "../../common/utils/ItemList";
