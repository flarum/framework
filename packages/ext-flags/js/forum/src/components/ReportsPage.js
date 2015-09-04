import Page from 'flarum/components/Page';

import ReportList from 'reports/components/ReportList';

/**
 * The `ReportsPage` component shows the reports list. It is only
 * used on mobile devices where the reports dropdown is within the drawer.
 */
export default class ReportsPage extends Page {
  constructor(...args) {
    super(...args);

    app.history.push('reports');

    this.list = new ReportList();
    this.list.load();

    this.bodyClass = 'App--reports';
  }

  view() {
    return <div className="ReportsPage">{this.list.render()}</div>;
  }
}
