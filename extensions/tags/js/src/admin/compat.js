import compat from '../common/compat';

import addTagsHomePageOption from './addTagsHomePageOption';
import addTagChangePermission from './addTagChangePermission';
import addTagsPane from './addTagsPane';
import TagSettingsModal from './components/TagSettingsModal';
import TagsPage from './components/TagsPage';
import EditTagModal from './components/EditTagModal';
import addTagPermission from './addTagPermission';
import addTagsPermissionScope from './addTagsPermissionScope';

export default Object.assign(compat, {
  'tags/addTagsHomePageOption': addTagsHomePageOption,
  'tags/addTagChangePermission': addTagChangePermission,
  'tags/addTagsPane': addTagsPane,
  'tags/components/TagSettingsModal': TagSettingsModal,
  'tags/components/TagsPage': TagsPage,
  'tags/components/EditTagModal': EditTagModal,
  'tags/addTagPermission': addTagPermission,
  'tags/addTagsPermissionScope': addTagsPermissionScope,
});
