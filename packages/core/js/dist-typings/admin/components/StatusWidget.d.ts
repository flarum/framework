export default class StatusWidget extends DashboardWidget {
    items(): ItemList<any>;
    toolsItems(): ItemList<any>;
    handleClearCache(e: any): void;
}
import DashboardWidget from "./DashboardWidget";
import ItemList from "../../common/utils/ItemList";
