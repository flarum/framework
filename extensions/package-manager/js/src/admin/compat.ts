import AuthMethodModal from './components/AuthMethodModal';
import ConfigureAuth from './components/ConfigureAuth';
import ConfigureComposer from './components/ConfigureComposer';
import ConfigureJson from './components/ConfigureJson';
import ControlSection from './components/ControlSection';
import ExtensionItem from './components/ExtensionItem';
import Installer from './components/Installer';
import Label from './components/Label';
import MajorUpdater from './components/MajorUpdater';
import Pagination from './components/Pagination';
import QueueSection from './components/QueueSection';
import RepositoryModal from './components/RepositoryModal';
import SettingsPage from './components/SettingsPage';
import TaskOutputModal from './components/TaskOutputModal';
import Updater from './components/Updater';
import WhyNotModal from './components/WhyNotModal';

import Task from './models/Task';

import ControlSectionState from './states/ControlSectionState';
import ExtensionManagerState from './states/ExtensionManagerState';
import QueueState from './states/QueueState';

import errorHandler from './utils/errorHandler';
import humanDuration from './utils/humanDuration';
import jumpToQueue from './utils/jumpToQueue';

export default {
  'extension-manager/components/AuthMethodModal': AuthMethodModal,
  'extension-manager/components/ConfigureAuth': ConfigureAuth,
  'extension-manager/components/ConfigureComposer': ConfigureComposer,
  'extension-manager/components/ConfigureJson': ConfigureJson,
  'extension-manager/components/ControlSection': ControlSection,
  'extension-manager/components/ExtensionItem': ExtensionItem,
  'extension-manager/components/Installer': Installer,
  'extension-manager/components/Label': Label,
  'extension-manager/components/MajorUpdater': MajorUpdater,
  'extension-manager/components/Pagination': Pagination,
  'extension-manager/components/QueueSection': QueueSection,
  'extension-manager/components/RepositoryModal': RepositoryModal,
  'extension-manager/components/SettingsPage': SettingsPage,
  'extension-manager/components/TaskOutputModal': TaskOutputModal,
  'extension-manager/components/Updater': Updater,
  'extension-manager/components/WhyNotModal': WhyNotModal,
  'extension-manager/models/Task': Task,
  'extension-manager/states/ControlSectionState': ControlSectionState,
  'extension-manager/states/ExtensionManagerState': ExtensionManagerState,
  'extension-manager/states/QueueState': QueueState,
  'extension-manager/utils/errorHandler': errorHandler,
  'extension-manager/utils/humanDuration': humanDuration,
  'extension-manager/utils/jumpToQueue': jumpToQueue,
};
