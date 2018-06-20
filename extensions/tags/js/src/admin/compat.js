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
  'addTagsHomePageOption': addTagsHomePageOption,
  'addTagChangePermission': addTagChangePermission,
  'addTagsPane': addTagsPane,
  'components/TagSettingsModal': TagSettingsModal,
  'components/TagsPage': TagsPage,
  'components/EditTagModal': EditTagModal,
  'addTagPermission': addTagPermission,
  'addTagsPermissionScope': addTagsPermissionScope,
});
