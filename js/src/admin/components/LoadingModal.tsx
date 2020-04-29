import { ComponentProps } from '../../common/Component';
import Modal from '../../common/components/Modal';

export default class LoadingModal extends Modal<ComponentProps> {
    isDismissible() {
        return false;
    }

    className() {
        return 'LoadingModal Modal--small';
    }

    title() {
        return app.translator.transText('core.admin.loading.title');
    }

    content() {
        return '';
    }
}
