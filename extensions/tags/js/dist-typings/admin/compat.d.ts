declare const _default: {
    'tags/utils/sortTags': typeof import("../common/utils/sortTags").default;
    'tags/models/Tag': typeof import("../common/models/Tag").default;
    'tags/helpers/tagsLabel': typeof import("../common/helpers/tagsLabel").default;
    'tags/helpers/tagIcon': typeof import("../common/helpers/tagIcon").default;
    'tags/helpers/tagLabel': typeof import("../common/helpers/tagLabel").default;
    'tags/components/TagSelectionModal': typeof import("../common/components/TagSelectionModal").default;
    'tags/states/TagListState': typeof import("../common/states/TagListState").default;
} & {
    'tags/addTagsHomePageOption': typeof addTagsHomePageOption;
    'tags/addTagChangePermission': typeof addTagChangePermission;
    'tags/components/TagsPage': typeof TagsPage;
    'tags/components/EditTagModal': typeof EditTagModal;
    'tags/addTagPermission': typeof addTagPermission;
    'tags/addTagsPermissionScope': typeof addTagsPermissionScope;
};
export default _default;
import addTagsHomePageOption from "./addTagsHomePageOption";
import addTagChangePermission from "./addTagChangePermission";
import TagsPage from "./components/TagsPage";
import EditTagModal from "./components/EditTagModal";
import addTagPermission from "./addTagPermission";
import addTagsPermissionScope from "./addTagsPermissionScope";
