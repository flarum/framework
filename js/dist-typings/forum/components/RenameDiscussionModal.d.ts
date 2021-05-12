/**
 * The 'RenameDiscussionModal' displays a modal dialog with an input to rename a discussion
 */
export default class RenameDiscussionModal extends Modal {
    discussion: any;
    currentTitle: any;
    newTitle: Stream<any> | undefined;
}
import Modal from "../../common/components/Modal";
import Stream from "../../common/utils/Stream";
