export default class ReadmeModal extends Modal<import("../../common/components/Modal").IInternalModalAttrs> {
    constructor();
    name: any;
    extName: any;
    loadReadme(): Promise<void>;
    readme: import("../../common/Store").ApiResponseSingle<import("../../common/Model").default> | undefined;
}
import Modal from "../../common/components/Modal";
