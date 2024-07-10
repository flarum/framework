import SuspendUserModal from './components/SuspendUserModal';
import SuspensionInfoModal from './components/SuspensionInfoModal';
import UserSuspendedNotification from './components/UserSuspendedNotification';
import UserUnsuspendedNotification from './components/UserUnsuspendedNotification';
import * as suspensionHelper from './helpers/suspensionHelper';
import checkForSuspension from './checkForSuspension';

export default {
  'suspend/components/suspendUserModal': SuspendUserModal,
  'suspend/components/suspensionInfoModal': SuspensionInfoModal,
  'suspend/components/UserSuspendedNotification': UserSuspendedNotification,
  'suspend/components/UserUnsuspendedNotification': UserUnsuspendedNotification,
  'suspend/helpers/suspensionHelper': suspensionHelper,
  'suspend/checkForSuspension': checkForSuspension,
};
