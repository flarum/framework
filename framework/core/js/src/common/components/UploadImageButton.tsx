import app from '../app';
import Button from './Button';
import type { IButtonAttrs } from './Button';
import classList from '../utils/classList';
import type Mithril from 'mithril';
import Component from '../Component';

export interface IUploadImageButtonAttrs extends IButtonAttrs {
  name: string;
  routePath: string;
  value?: string | null | (() => string | null);
  url?: string | null | (() => string | null);
}

export default class UploadImageButton<CustomAttrs extends IUploadImageButtonAttrs = IUploadImageButtonAttrs> extends Component<CustomAttrs> {
  loading = false;

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    let { name, value, url, ...attrs } = vnode.attrs as IButtonAttrs;

    attrs.loading = this.loading;
    attrs.className = classList(attrs.className, 'Button');

    if (typeof value === 'function') {
      value = value();
    }

    if (typeof url === 'function') {
      url = url();
    }

    return (
      <div className="UploadImageButton">
        {value ? (
          <>
            <div className="UploadImageButton-image">
              <img src={url} alt={name} />
            </div>
            <Button onclick={this.remove.bind(this)} {...attrs}>
              {app.translator.trans('core.admin.upload_image.remove_button')}
            </Button>
          </>
        ) : (
          <Button onclick={this.upload.bind(this)} {...attrs}>
            {app.translator.trans('core.admin.upload_image.upload_button')}
          </Button>
        )}
      </div>
    );
  }

  upload() {
    if (this.loading) return;

    const $input = $('<input type="file">');

    $input
      .appendTo('body')
      .hide()
      .trigger('click')
      .on('change', (e) => {
        const body = new FormData();
        // @ts-ignore
        body.append(this.attrs.name, $(e.target)[0].files[0]);

        this.loading = true;
        m.redraw();

        app
          .request({
            method: 'POST',
            url: this.resourceUrl(),
            serialize: (raw) => raw,
            body,
          })
          .then(this.success.bind(this), this.failure.bind(this));
      });
  }

  remove() {
    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'DELETE',
        url: this.resourceUrl(),
      })
      .then(this.success.bind(this), this.failure.bind(this));
  }

  resourceUrl() {
    return app.forum.attribute('apiUrl') + '/' + this.attrs.routePath;
  }

  /**
   * After a successful upload/removal, reload the page.
   */
  protected success(response: any) {
    window.location.reload();
  }

  /**
   * If upload/removal fails, stop loading.
   */
  protected failure(response: any) {
    this.loading = false;
    m.redraw();
  }
}
