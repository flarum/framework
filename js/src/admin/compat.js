import compat from '../common/compat';

import saveSettings from './utils/saveSettings';
import ExtensionData from './utils/ExtensionData';
import isExtensionEnabled from './utils/isExtensionEnabled';
import getCategorizedExtensions from './utils/getCategorizedExtensions';
import SettingDropdown from './components/SettingDropdown';
import EditCustomFooterModal from './components/EditCustomFooterModal';
import SessionDropdown from './components/SessionDropdown';
import HeaderPrimary from './components/HeaderPrimary';
import AdminPage from './components/AdminPage';
import AppearancePage from './components/AppearancePage';
import StatusWidget from './components/StatusWidget';
import ExtensionsWidget from './components/ExtensionsWidget';
import HeaderSecondary from './components/HeaderSecondary';
import SettingsModal from './components/SettingsModal';
import DashboardWidget from './components/DashboardWidget';
import ExtensionPage from './components/ExtensionPage';
import ExtensionLinkButton from './components/ExtensionLinkButton';
import PermissionGrid from './components/PermissionGrid';
import ExtensionPermissionGrid from './components/ExtensionPermissionGrid';
import MailPage from './components/MailPage';
import UploadImageButton from './components/UploadImageButton';
import LoadingModal from './components/LoadingModal';
import DashboardPage from './components/DashboardPage';
import BasicsPage from './components/BasicsPage';
import UserListPage from './components/UserListPage';
import EditCustomHeaderModal from './components/EditCustomHeaderModal';
import PermissionsPage from './components/PermissionsPage';
import PermissionDropdown from './components/PermissionDropdown';
import AdminNav from './components/AdminNav';
import AdminHeader from './components/AdminHeader';
import EditCustomCssModal from './components/EditCustomCssModal';
import EditGroupModal from './components/EditGroupModal';
import routes from './routes';
import AdminApplication from './AdminApplication';

export default Object.assign(compat, {
  'utils/saveSettings': saveSettings,
  'utils/ExtensionData': ExtensionData,
  'utils/isExtensionEnabled': isExtensionEnabled,
  'utils/getCategorizedExtensions': getCategorizedExtensions,
  'components/SettingDropdown': SettingDropdown,
  'components/EditCustomFooterModal': EditCustomFooterModal,
  'components/SessionDropdown': SessionDropdown,
  'components/HeaderPrimary': HeaderPrimary,
  'components/AdminPage': AdminPage,
  'components/AppearancePage': AppearancePage,
  'components/StatusWidget': StatusWidget,
  'components/ExtensionsWidget': ExtensionsWidget,
  'components/HeaderSecondary': HeaderSecondary,
  'components/SettingsModal': SettingsModal,
  'components/DashboardWidget': DashboardWidget,
  'components/ExtensionPage': ExtensionPage,
  'components/ExtensionLinkButton': ExtensionLinkButton,
  'components/PermissionGrid': PermissionGrid,
  'components/ExtensionPermissionGrid': ExtensionPermissionGrid,
  'components/MailPage': MailPage,
  'components/UploadImageButton': UploadImageButton,
  'components/LoadingModal': LoadingModal,
  'components/DashboardPage': DashboardPage,
  'components/BasicsPage': BasicsPage,
  'components/UserListPage': UserListPage,
  'components/EditCustomHeaderModal': EditCustomHeaderModal,
  'components/PermissionsPage': PermissionsPage,
  'components/PermissionDropdown': PermissionDropdown,
  'components/AdminNav': AdminNav,
  'components/AdminHeader': AdminHeader,
  'components/EditCustomCssModal': EditCustomCssModal,
  'components/EditGroupModal': EditGroupModal,
  routes: routes,
  AdminApplication: AdminApplication,
});
