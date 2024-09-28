import addFlagsToPosts from './addFlagsToPosts';
import addFlagControl from './addFlagControl';
import addFlagsDropdown from './addFlagsDropdown';
import Flag from './models/Flag';
import FlagList from './components/FlagList';
import FlagPostModal from './components/FlagPostModal';
import FlagsPage from './components/FlagsPage';
import FlagsDropdown from './components/FlagsDropdown';

export default {
  'flags/addFlagsToPosts': addFlagsToPosts,
  'flags/addFlagControl': addFlagControl,
  'flags/addFlagsDropdown': addFlagsDropdown,
  'flags/models/Flag': Flag,
  'flags/components/FlagList': FlagList,
  'flags/components/FlagPostModal': FlagPostModal,
  'flags/components/FlagsPage': FlagsPage,
  'flags/components/FlagsDropdown': FlagsDropdown,
};
