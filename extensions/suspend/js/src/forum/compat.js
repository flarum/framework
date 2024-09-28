import SuspendUserModal from './components/SuspendUserModal';
import SuspensionInfoModal from './components/SuspensionInfoModal';
import UserSuspendedNotification from './components/UserSuspendedNotification';
import UserUnsuspendedNotification from './components/UserUnsuspendedNotification';
import checkForSuspension from './checkForSuspension';

export default {
  'suspend/components/suspendUserModal': SuspendUserModal,
  'suspend/components/suspensionInfoModal': SuspensionInfoModal,
  'suspend/components/UserSuspendedNotification': UserSuspendedNotification,
  'suspend/components/UserUnsuspendedNotification': UserUnsuspendedNotification,
  'suspend/checkForSuspension': checkForSuspension,
};
