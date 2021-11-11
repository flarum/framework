export default class PermissionGrid extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    permissionItems(): ItemList<any>;
    viewItems(): ItemList<any>;
    startItems(): ItemList<any>;
    replyItems(): ItemList<any>;
    moderateItems(): ItemList<any>;
    scopeItems(): ItemList<any>;
    scopeControlItems(): ItemList<any>;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
