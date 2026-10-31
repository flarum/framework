import app from '../../admin/app';
import DashboardWidget from './DashboardWidget';
import type { IDashboardWidgetAttrs } from './DashboardWidget';
import type Mithril from 'mithril';
import Icon from '../../common/components/Icon';
import Button from '../../common/components/Button';
import Tooltip from '../../common/components/Tooltip';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Link from '../../common/components/Link';

interface BounceStats {
  hour: number;
  week: number;
  month: number;
  total: number;
  affected: number;
  recovered: number;
  configured: boolean;
}

export default class EmailBounceWidget extends DashboardWidget {
  stats: BounceStats | null = null;
  loading = false;
  loadError = false;

  oninit(vnode: Mithril.Vnode<IDashboardWidgetAttrs, this>) {
    super.oninit(vnode);
    this.load();
  }

  className() {
    return 'EmailBounceWidget';
  }

  async load() {
    this.loading = true;
    this.loadError = false;
    m.redraw();

    try {
      const data = (await app.request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/mail/bounce-stats',
      })) as unknown as BounceStats;
      this.stats = data;
    } catch (e) {
      this.loadError = true;
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  content() {
    return (
      <>
        <div className="EmailBounceWidget-header">
          <h2 className="EmailBounceWidget-title">
            <Icon name="fas fa-envelope-open-text" />
            {app.translator.trans('core.admin.email_bounces.title')}
            <Tooltip text={app.translator.trans('core.admin.email_bounces.about')}>
              <span className="EmailBounceWidget-info">
                <Icon name="fas fa-info-circle" />
              </span>
            </Tooltip>
          </h2>
          <div className="EmailBounceWidget-controls">
            <Tooltip text={app.translator.trans('core.admin.email_bounces.refresh')}>
              <Button
                className="Button Button--icon"
                icon={this.loading ? 'fas fa-sync-alt fa-spin' : 'fas fa-sync-alt'}
                disabled={this.loading}
                onclick={() => this.load()}
              />
            </Tooltip>
          </div>
        </div>
        {this.body()}
      </>
    );
  }

  body(): Mithril.Children {
    if (this.loading && !this.stats) {
      return <LoadingIndicator />;
    }

    if (this.loadError) {
      return <p className="EmailBounceWidget-error">{app.translator.trans('core.admin.email_bounces.load_error')}</p>;
    }

    if (!this.stats) {
      return null;
    }

    // Event-volume counts over each time window.
    const periods: Array<{ key: keyof BounceStats; label: string }> = [
      { key: 'hour', label: app.translator.trans('core.admin.email_bounces.period_hour', {}, true) },
      { key: 'week', label: app.translator.trans('core.admin.email_bounces.period_week', {}, true) },
      { key: 'month', label: app.translator.trans('core.admin.email_bounces.period_month', {}, true) },
      { key: 'total', label: app.translator.trans('core.admin.email_bounces.period_total', {}, true) },
    ];

    return (
      <div className="EmailBounceWidget-body">
        {!this.stats.configured && (
          <div className="EmailBounceWidget-notConfigured">
            <Icon name="fas fa-exclamation-triangle" />
            <span>
              {app.translator.trans('core.admin.email_bounces.not_configured', {
                a: <Link href={app.route('mail')} />,
              })}
            </span>
          </div>
        )}
        <div className="EmailBounceWidget-figures">
          {periods.map((period) => (
            <div className="EmailBounceWidget-figure" key={period.key}>
              <div className="EmailBounceWidget-figureCount">{this.stats![period.key].toLocaleString()}</div>
              <div className="EmailBounceWidget-figureLabel">{period.label}</div>
            </div>
          ))}
        </div>

        {/* Current status: how many addresses are still broken vs recovered. */}
        <div className="EmailBounceWidget-status">
          <span className="EmailBounceWidget-status-affected">
            {app.translator.trans('core.admin.email_bounces.affected', { count: this.stats.affected })}
          </span>
          <span className="EmailBounceWidget-status-recovered">
            {app.translator.trans('core.admin.email_bounces.recovered', { count: this.stats.recovered })}
          </span>
        </div>

        {this.stats.affected > 0 && (
          <Link className="EmailBounceWidget-viewAll" href={app.route('users', { filter: 'is:bounced' })}>
            {app.translator.trans('core.admin.email_bounces.view_affected')}
          </Link>
        )}
      </div>
    );
  }
}
