export default class PermissionGrid extends Component<import("../../common/Component").ComponentAttrs> {
    constructor();
    permissionItems(): ItemList;
    viewItems(): ItemList;
    startItems(): ItemList;
    replyItems(): ItemList;
    moderateItems(): ItemList;
    scopeItems(): ItemList;
    scopeControlItems(): ItemList;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
