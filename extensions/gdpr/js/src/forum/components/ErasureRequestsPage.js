import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';

import ErasureRequestsList from './ErasureRequestsList';

export default class ErasureRequestsPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    app.history.push('erasure-requests');

    app.erasureRequests.load();

    this.bodyClass = 'App--ErasureRequests';
  }

  view() {
    return (
      <div className="ErasureRequestsPage">
        <ErasureRequestsList state={app.erasureRequests}></ErasureRequestsList>
      </div>
    );
  }
}
