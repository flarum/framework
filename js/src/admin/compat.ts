import compat from '../common/compat';

import Admin from './Admin';
import SettingsModal from './components/SettingsModal';
import UploadImageButton from './components/UploadImageButton';

export default Object.assign(compat, {
    Admin: Admin,
    SettingsModal: SettingsModal,
    UploadImageButton: UploadImageButton,
}) as any;
