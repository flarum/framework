import app from '../../admin/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Button from '../../common/components/Button';
import type Mithril from 'mithril';

export default class InfoModal extends Modal<IInternalModalAttrs> {
  protected info: string = '';

  oninit(vnode: Mithril.Vnode<IInternalModalAttrs, this>) {
    super.oninit(vnode);

    this.loading = true;

    this.loadInfo();
  }

  className() {
    return 'InfoModal Modal--large';
  }

  title() {
    return app.translator.trans('core.admin.dashboard.info_modal.title');
  }

  content() {
    return (
      <div className="Modal-body">
        {this.loading ? (
          <div className="InfoModal-loading">
            <LoadingIndicator />
          </div>
        ) : (
          <div>
            <div className="InfoModal-actions">
              <Button className="Button Button--primary" onclick={this.copyToClipboard.bind(this)}>
                {app.translator.trans('core.admin.dashboard.info_modal.copy_button')}
              </Button>
            </div>
            <pre className="InfoModal-content">
              <code>{this.info}</code>
            </pre>
          </div>
        )}
      </div>
    );
  }

  copyToClipboard() {
    navigator.clipboard.writeText(this.info).then(
      () => {
        app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.dashboard.info_modal.copy_success'));
      },
      () => {
        app.alerts.show({ type: 'error' }, app.translator.trans('core.admin.dashboard.info_modal.copy_error'));
      }
    );
  }

  async loadInfo() {
    try {
      const response = await app.request<any>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/system-info/system',
      });

      this.info = response.data?.attributes?.content || 'No info available';
    } catch (error) {
      this.info = 'Error loading info: ' + (error as Error).message;
    }

    this.loading = false;
    m.redraw();
  }
}
