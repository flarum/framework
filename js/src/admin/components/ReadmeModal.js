import app from '../../admin/app';
import Modal from '../../common/components/Modal';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Placeholder from '../../common/components/Placeholder';
import ExtensionReadme from '../models/ExtensionReadme';

export default class ReadmeModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);

    app.store.models['extension-readmes'] = ExtensionReadme;

    this.name = this.attrs.extension.id;
    this.extName = this.attrs.extension.extra['flarum-extension'].title;

    this.loading = true;

    this.loadReadme();
  }

  className() {
    return 'ReadmeModal Modal--large';
  }

  title() {
    return app.translator.trans('core.admin.extension.readme.title', {
      extName: this.extName,
    });
  }

  content() {
    const text = app.translator.trans('core.admin.extension.readme.no_readme');

    return (
      <div className="container">
        {this.loading ? (
          <div className="ReadmeModal-loading">{LoadingIndicator.component()}</div>
        ) : (
          <div>{this.readme.content() ? m.trust(this.readme.content()) : Placeholder.component({ text })}</div>
        )}
      </div>
    );
  }

  async loadReadme() {
    this.readme = await app.store.find('extension-readmes', this.name);
    this.loading = false;
    m.redraw();
  }
}
