/**
 * The `EditGroupModal` component shows a modal dialog which allows the user
 * to create or edit a group.
 */
export default class EditGroupModal extends Modal {
    group: any;
    nameSingular: Stream<any> | undefined;
    namePlural: Stream<any> | undefined;
    icon: Stream<any> | undefined;
    color: Stream<any> | undefined;
    isHidden: Stream<any> | undefined;
    fields(): ItemList;
    submitData(): {
        nameSingular: any;
        namePlural: any;
        color: any;
        icon: any;
        isHidden: any;
    };
    deleteGroup(): void;
}
import Modal from "../../common/components/Modal";
import Stream from "../../common/utils/Stream";
import ItemList from "../../common/utils/ItemList";
