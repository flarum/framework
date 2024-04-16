import Modal, { IDismissibleOptions, type IInternalModalAttrs } from '../../common/components/Modal';
import Mithril from 'mithril';
import app from '../app';
import Button from '../../common/components/Button';
import Form from '../../common/components/Form';
import Stream from '../../common/utils/Stream';
import Icon from '../../common/components/Icon';

type BisectResult = {
  stepsLeft: number;
  relevantEnabled: string[];
  relevantDisabled: string[];
  extension: string | null;
};

export default class ExtensionBisect<CustomAttrs extends IInternalModalAttrs = IInternalModalAttrs> extends Modal<CustomAttrs> {
  private result = Stream<BisectResult | null>(null);
  private bisecting = Stream<boolean>(app.data.bisecting || false);

  protected static readonly isDismissibleViaCloseButton: boolean = true;
  protected static readonly isDismissibleViaEscKey: boolean = false;
  protected static readonly isDismissibleViaBackdropClick: boolean = false;

  protected get dismissibleOptions(): IDismissibleOptions {
    return {
      viaCloseButton: !this.bisecting(),
      viaEscKey: !this.bisecting(),
      viaBackdropClick: !this.bisecting(),
    };
  }

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    if (m.route.param('modal') !== 'extension-bisect') {
      window.history.replaceState({}, '', window.location.pathname + '#' + app.route('advanced', { modal: 'extension-bisect' }));
    }
  }

  className(): string {
    return 'ExtensionBisectModal Modal--small';
  }

  title(): Mithril.Children {
    return app.translator.trans('core.admin.advanced.maintenance.bisect_modal.title');
  }

  content(): Mithril.Children {
    const result = this.result();

    if (result && result.extension) {
      const extension = app.data.extensions[result.extension];

      return (
        <div className="Modal-body">
          <Form className="Form--centered">
            <p className="helpText">{app.translator.trans('core.admin.advanced.maintenance.bisect_modal.result_description')}</p>
            <div className="ExtensionBisectModal-extension">
              <div className="ExtensionBisectModal-extension-icon ExtensionIcon" style={extension.icon}>
                {extension.icon ? <Icon name={extension.icon.name} /> : ''}
              </div>
              <div className="ExtensionBisectModal-extension-info">
                <div className="ExtensionBisectModal-extension-name">{extension.extra['flarum-extension'].title}</div>
                <div className="ExtensionBisectModal-extension-version">
                  <span className="ExtensionBisectModal-extension-version">{extension.version}</span>
                </div>
              </div>
            </div>
            <Button className="Button Button--primary" onclick={() => this.hide(extension.id)}>
              {app.translator.trans('core.admin.advanced.maintenance.bisect_modal.end_button')}
            </Button>
          </Form>
        </div>
      );
    }

    return (
      <div className="Modal-body">
        <Form className="Form--centered">
          <p className="helpText">{app.translator.trans('core.admin.advanced.maintenance.bisect_modal.description')}</p>
          <p className="helpText">
            {app.translator.trans('core.admin.advanced.maintenance.bisect_modal.' + (this.bisecting() ? 'steps_left' : 'total_steps'), {
              steps: this.stepsLeft(),
            })}
          </p>
          {this.bisecting() ? (
            <div className="Form-group">
              <label>{app.translator.trans('core.admin.advanced.maintenance.bisect_modal.issue_question')}</label>
              <p className="helpText">{app.translator.trans('core.admin.advanced.maintenance.bisect_modal.issue_question_help')}</p>
            </div>
          ) : null}
          {this.bisecting() ? (
            <>
              <div className="ButtonGroup ButtonGroup--block">
                <Button className="Button Button--danger" onclick={() => this.submit(true)} loading={this.loading}>
                  {app.translator.trans('core.admin.advanced.maintenance.bisect_modal.yes_button')}
                </Button>
                <Button className="Button Button--success" onclick={() => this.submit(false)} loading={this.loading}>
                  {app.translator.trans('core.admin.advanced.maintenance.bisect_modal.no_button')}
                </Button>
              </div>
              <Button className="Button Button--primary Button--block" onclick={() => this.submit(null, true)} loading={this.loading}>
                {app.translator.trans('core.admin.advanced.maintenance.bisect_modal.stop_button')}
              </Button>
            </>
          ) : (
            <Button className="Button Button--primary Button--block" onclick={() => this.submit(true)} loading={this.loading}>
              {app.translator.trans('core.admin.advanced.maintenance.bisect_modal.start_button')}
            </Button>
          )}
        </Form>
      </div>
    );
  }

  stepsLeft(): number {
    if (this.result()) {
      return this.result()!.stepsLeft;
    }

    let steps;

    if (this.bisecting()) {
      const { low, high } = JSON.parse(app.data.settings.extension_bisect_state);
      steps = Math.ceil(Math.log2(high - low)) + 1;
    } else {
      steps = Math.ceil(Math.log2(JSON.parse(app.data.settings.extensions_enabled).length)) + 1;
    }

    return steps;
  }

  submit(issue: boolean | null, end: boolean = false) {
    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/extension-bisect',
        body: { issue, end },
      })
      .then((response) => {
        this.loading = false;
        this.bisecting(!end);
        this.result(response as BisectResult);
        m.redraw();

        if (end) {
          this.hide();
        }
      });
  }

  hide(extension?: string) {
    this.attrs.animateHide(() => {
      if (extension) {
        m.route.set(app.route('extension', { id: extension }));
      } else {
        m.route.set(app.route('advanced'));
      }

      window.location.reload();
    });
  }
}
