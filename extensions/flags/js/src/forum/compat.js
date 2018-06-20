import addFlagsToPosts from './addFlagsToPosts';
import addFlagControl from './addFlagControl';
import addFlagsDropdown from './addFlagsDropdown';
import Flag from './models/Flag';
import FlagList from './components/FlagList';
import FlagPostModal from './components/FlagPostModal';
import FlagsPage from './components/FlagsPage';
import FlagsDropdown from './components/FlagsDropdown';

export default {
  'addFlagsToPosts': addFlagsToPosts,
  'addFlagControl': addFlagControl,
  'addFlagsDropdown': addFlagsDropdown,
  'models/Flag': Flag,
  'components/FlagList': FlagList,
  'components/FlagPostModal': FlagPostModal,
  'components/FlagsPage': FlagsPage,
  'components/FlagsDropdown': FlagsDropdown,
};
