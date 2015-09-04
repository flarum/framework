import app from 'flarum/app';
import Model from 'flarum/Model';

import Report from 'reports/models/Report';
import ReportsPage from 'reports/components/ReportsPage';
import addReportControl from 'reports/addReportControl';
import addReportsDropdown from 'reports/addReportsDropdown';
import addReportsToPosts from 'reports/addReportsToPosts';

app.initializers.add('reports', () => {
  app.store.models.posts.prototype.reports = Model.hasMany('reports');
  app.store.models.posts.prototype.canReport = Model.attribute('canReport');

  app.store.models.reports = Report;

  app.routes.reports = {path: '/reports', component: <ReportsPage/>};

  addReportControl();
  addReportsDropdown();
  addReportsToPosts();
});
