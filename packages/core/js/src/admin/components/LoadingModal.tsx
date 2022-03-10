import app from '../../admin/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';

export interface ILoadingModalAttrs extends IInternalModalAttrs {}

export default class LoadingModal<ModalAttrs extends ILoadingModalAttrs = ILoadingModalAttrs> extends Modal<ModalAttrs> {
  /**
   * @inheritdoc
   */
  static readonly isDismissible: boolean = false;

  className() {
    return 'LoadingModal Modal--small';
  }

  title() {
    return app.translator.trans('core.admin.loading.title');
  }

  content() {
    return '';
  }

  onsubmit(e: Event): void {
    throw new Error('LoadingModal should not throw errors.');
  }
}
