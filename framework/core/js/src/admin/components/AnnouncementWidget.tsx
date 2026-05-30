import app from '../../admin/app';
import DashboardWidget from './DashboardWidget';
import AnnouncementList from './AnnouncementList';
import type { AnnouncementData } from './AnnouncementItem';
import type { IDashboardWidgetAttrs } from './DashboardWidget';
import type Mithril from 'mithril';
import icon from '../../common/helpers/icon';
import Button from '../../common/components/Button';
import Tooltip from '../../common/components/Tooltip';

const HIDDEN_KEY = 'flarum.announcements.hidden';

export default class AnnouncementsWidget extends DashboardWidget {
  announcements: AnnouncementData[] | null = null;
  loadError = false;
  loading = false;
  hidden = false;

  oninit(vnode: Mithril.Vnode<IDashboardWidgetAttrs, this>) {
    super.oninit(vnode);
    this.hidden = localStorage.getItem(HIDDEN_KEY) === '1';

    if (!this.hidden) {
      this.load();
    }
  }

  className() {
    return 'AnnouncementsWidget' + (this.hidden ? ' AnnouncementsWidget--hidden' : '');
  }

  async load(bust = false) {
    this.loading = true;
    this.loadError = false;
    m.redraw();

    try {
      const url = app.forum.attribute('apiUrl') + '/flarum/announcements' + (bust ? '?bust=1' : '');
      const data = (await app.request({ method: 'GET', url })) as unknown as AnnouncementData[];
      this.announcements = data;
    } catch (e) {
      this.loadError = true;
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  toggleHidden() {
    this.hidden = !this.hidden;

    if (this.hidden) {
      localStorage.setItem(HIDDEN_KEY, '1');
    } else {
      localStorage.removeItem(HIDDEN_KEY);
      if (!this.announcements && !this.loading) {
        this.load();
      }
    }

    m.redraw();
  }

  content() {
    return (
      <>
        <div className="AnnouncementsWidget-header">
          <h2 className="AnnouncementsWidget-title">
            {icon('fas fa-bullhorn')}
            {app.translator.trans('core.admin.announcements.title')}
            <Tooltip text={app.translator.trans('core.admin.announcements.about')}>
              <span className="AnnouncementsWidget-info">{icon('fas fa-info-circle')}</span>
            </Tooltip>
          </h2>
          <div className="AnnouncementsWidget-controls">
            {!this.hidden && (
              <Tooltip text={app.translator.trans('core.admin.announcements.refresh')}>
                <Button
                  className="Button Button--icon"
                  icon={this.loading ? 'fas fa-sync-alt fa-spin' : 'fas fa-sync-alt'}
                  disabled={this.loading}
                  onclick={() => this.load(true)}
                />
              </Tooltip>
            )}
            <Tooltip text={app.translator.trans(this.hidden ? 'core.admin.announcements.show' : 'core.admin.announcements.hide')}>
              <Button className="Button Button--icon" icon={this.hidden ? 'fas fa-eye' : 'fas fa-eye-slash'} onclick={() => this.toggleHidden()} />
            </Tooltip>
          </div>
        </div>
        {!this.hidden && (
          <AnnouncementList announcements={this.announcements} loading={this.loading} error={this.loadError} onRetry={() => this.load()} />
        )}
      </>
    );
  }
}
