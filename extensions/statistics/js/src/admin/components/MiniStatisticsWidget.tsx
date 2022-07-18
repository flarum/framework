import app from 'flarum/admin/app';

import DashboardWidget, { IDashboardWidgetAttrs } from 'flarum/admin/components/DashboardWidget';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Link from 'flarum/common/components/Link';

import abbreviateNumber from 'flarum/common/utils/abbreviateNumber';

import type Mithril from 'mithril';

export default class MiniStatisticsWidget extends DashboardWidget {
  entities = ['users', 'discussions', 'posts'];

  lifetimeData: any;

  loadingLifetime = true;

  oncreate(vnode: Mithril.VnodeDOM<IDashboardWidgetAttrs, this>) {
    super.oncreate(vnode);

    this.loadLifetimeData();
  }

  async loadLifetimeData() {
    this.loadingLifetime = true;
    m.redraw();

    const data = await app.request({
      method: 'GET',
      url: app.forum.attribute('apiUrl') + '/statistics',
      params: {
        period: 'lifetime',
      },
    });

    this.lifetimeData = data;
    this.loadingLifetime = false;

    m.redraw();
  }

  className() {
    return 'StatisticsWidget StatisticsWidget--mini';
  }

  content() {
    return (
      <div className="StatisticsWidget-table">
        <h4 className="StatisticsWidget-title">{app.translator.trans('flarum-statistics.admin.statistics.mini_heading')}</h4>

        <div className="StatisticsWidget-entities">
          <div className="StatisticsWidget-labels">
            <div className="StatisticsWidget-label">{app.translator.trans('flarum-statistics.admin.statistics.total_label')}</div>
          </div>

          {this.entities.map((entity) => {
            const totalCount = this.loadingLifetime ? app.translator.trans('flarum-statistics.admin.statistics.loading') : this.getTotalCount(entity);

            return (
              <div className="StatisticsWidget-entity">
                <h3 className="StatisticsWidget-heading">{app.translator.trans('flarum-statistics.admin.statistics.' + entity + '_heading')}</h3>
                <div className="StatisticsWidget-total" title={totalCount}>
                  {this.loadingLifetime ? <LoadingIndicator display="inline" /> : abbreviateNumber(totalCount as number)}
                </div>
              </div>
            );
          })}
        </div>

        <div className="StatisticsWidget-viewFull">
          <Link href={app.route('extension', { id: 'flarum-statistics' })}>
            {app.translator.trans('flarum-statistics.admin.statistics.view_full')}
          </Link>
        </div>
      </div>
    );
  }

  getTotalCount(entity: string): number {
    return this.lifetimeData[entity];
  }
}
