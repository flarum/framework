import type Mithril from 'mithril';
import app from 'flarum/admin/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

import errorHandler from '../utils/errorHandler';

export interface WhyNotModalAttrs extends IInternalModalAttrs {
  package: string;
}

export default class WhyNotModal extends Modal<WhyNotModalAttrs> {
  loading: boolean = true;
  whyNot: string | null = null;

  className() {
    return 'Modal--large WhyNotModal';
  }

  title() {
    return app.translator.trans('flarum-package-manager.admin.why_not_modal.title');
  }

  oncreate(vnode: Mithril.VnodeDOM<WhyNotModalAttrs, this>) {
    super.oncreate(vnode);

    this.requestWhyNot();
  }

  content() {
    return <div className="Modal-body">{this.loading ? <LoadingIndicator /> : <pre className="WhyNotModal-contents">{this.whyNot}</pre>}</div>;
  }

  requestWhyNot(): void {
    app
      .request({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/package-manager/why-not`,
        body: {
          data: {
            package: this.attrs.package,
          },
        },
        errorHandler,
      })
      .then((response: any) => {
        this.loading = false;
        this.whyNot = response.data.whyNot;
        m.redraw();
      });
  }
}
