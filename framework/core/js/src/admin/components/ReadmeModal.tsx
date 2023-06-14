import app from '../../admin/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Placeholder from '../../common/components/Placeholder';
import ExtensionReadme from '../models/ExtensionReadme';
import type Mithril from 'mithril';
import type { Extension } from '../AdminApplication';

export interface IReadmeModalAttrs extends IInternalModalAttrs {
  extension: Extension;
}

export default class ReadmeModal<CustomAttrs extends IReadmeModalAttrs = IReadmeModalAttrs> extends Modal<CustomAttrs> {
  protected name!: string;
  protected extName!: string;
  protected readme!: ExtensionReadme;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    app.store.models['extension-readmes'] = ExtensionReadme;

    this.name = this.attrs.extension.id;
    this.extName = this.attrs.extension.extra['flarum-extension'].title;

    this.loading = true;

    this.loadReadme();
  }

  className() {
    return 'ReadmeModal Modal--large Modal--inverted';
  }

  title() {
    return app.translator.trans('core.admin.extension.readme.title', {
      extName: this.extName,
    });
  }

  content() {
    return (
      <div className="Modal-body">
        {this.loading ? (
          <div className="ReadmeModal-loading">
            <LoadingIndicator />
          </div>
        ) : (
          <div>
            {this.readme.content() ? (
              m.trust(this.readme.content())
            ) : (
              <Placeholder text={app.translator.trans('core.admin.extension.readme.no_readme')} />
            )}
          </div>
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
