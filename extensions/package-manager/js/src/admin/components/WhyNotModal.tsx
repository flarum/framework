import type Mithril from 'mithril';
import app from 'flarum/admin/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

import errorHandler from '../utils/errorHandler';

type WhyNotResponse = {
  data: {
    reason: string;
  };
};

export interface WhyNotModalAttrs extends IInternalModalAttrs {
  package: string;
}

export default class WhyNotModal<CustomAttrs extends WhyNotModalAttrs = WhyNotModalAttrs> extends Modal<CustomAttrs> {
  loading: boolean = true;
  whyNot: string | null = null;

  className() {
    return 'Modal--large WhyNotModal';
  }

  title() {
    return app.translator.trans('flarum-extension-manager.admin.why_not_modal.title');
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    this.requestWhyNot();
  }

  content() {
    return <div className="Modal-body">{this.loading ? <LoadingIndicator /> : <pre className="WhyNotModal-contents">{this.whyNot}</pre>}</div>;
  }

  requestWhyNot(): void {
    app
      .request<WhyNotResponse>({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/extension-manager/why-not`,
        body: {
          data: {
            package: this.attrs.package,
          },
        },
      })
      .then((response) => {
        this.loading = false;
        this.whyNot = response.data.reason;
        m.redraw();
      })
      .catch(errorHandler);
  }
}
