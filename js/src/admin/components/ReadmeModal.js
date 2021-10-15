import app from '../../admin/app';
import Modal from '../../common/components/Modal';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Placeholder from '../../common/components/Placeholder';
import ExtensionReadme from '../models/ExtensionReadme';

export default class ReadmeModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);

    app.store.models['extensions-readme'] = ExtensionReadme;  

    this.name = this.attrs.extension.id;
    this.displayName = this.attrs.extension.extra['flarum-extension'].title;

    this.loading = true;

    this.loadReadme();
  }

  className() {
    return 'ReadmeModal Modal--large';
  }

  title() {
    return app.translator.trans('core.admin.extension.readme.title', {
      displayName: this.displayName,
    });
  }

  content() {
    const text = app.translator.trans('core.admin.extension.readme.no_readme');
    console.log(this.readme);
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

  loadReadme() {
    // app
    //   .request({
    //     method: 'GET',
    //     url: app.forum.attribute('apiUrl') + '/extensions-readme/' + this.name,
    //   })
    //   .then(this.parseResponse.bind(this));
    const ext = this.name;

    app.store.find('extensions-readme', ext).then(this.parseResponse.bind(this));
  }

  parseResponse(response) {
    this.readme = response.data;

    this.loading = false;
    m.redraw();
  }
}
