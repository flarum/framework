import app from 'flarum/admin/app';

import SelectDropdown from 'flarum/common/components/SelectDropdown';
import Button from 'flarum/common/components/Button';
import abbreviateNumber from 'flarum/common/utils/abbreviateNumber';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import icon from 'flarum/common/helpers/icon';

import DashboardWidget, { IDashboardWidgetAttrs } from 'flarum/admin/components/DashboardWidget';

import type Mithril from 'mithril';

// @ts-expect-error No typings available
import { Chart } from 'frappe-charts';

interface IPeriodDeclaration {
  start: number;
  end: number;
  step: number;
}

export default class StatisticsWidget extends DashboardWidget {
  entities = ['users', 'discussions', 'posts'];
  periods: undefined | Record<string, IPeriodDeclaration>;

  chart: any;

  timedData: Record<string, undefined | any> = {};
  lifetimeData: any;

  loadingLifetime = true;
  loadingTimed: Record<string, 'unloaded' | 'loading' | 'loaded' | 'fail'> = this.entities.reduce((acc, curr) => {
    acc[curr] = 'unloaded';
    return acc;
  }, {} as Record<string, 'unloaded' | 'loading' | 'loaded' | 'fail'>);

  selectedEntity = 'users';
  selectedPeriod: undefined | string;

  chartEntity?: string;
  chartPeriod?: string;

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

  async loadTimedData(model: string) {
    this.loadingTimed[model] = 'loading';
    m.redraw();

    try {
      const data = await app.request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/statistics',
        params: {
          period: 'timed',
          model,
        },
      });

      this.timedData[model] = data;
      this.loadingTimed[model] = 'loaded';

      // Create a Date object which represents the start of the day.
      let todayDate = new Date();
      todayDate.setUTCHours(0, 0, 0, 0);

      const today = todayDate.getTime() / 1000;

      this.periods = {
        today: { start: today, end: today + 86400, step: 3600 },
        last_7_days: { start: today - 86400 * 7, end: today, step: 86400 },
        previous_7_days: { start: today - 86400 * 14, end: today - 86400 * 7, step: 86400 },
        last_28_days: { start: today - 86400 * 28, end: today, step: 86400 },
        previous_28_days: { start: today - 86400 * 28 * 2, end: today - 86400 * 28, step: 86400 },
        last_12_months: { start: today - 86400 * 364, end: today, step: 86400 * 7 },
      };

      this.selectedPeriod = 'last_7_days';
    } catch (e) {
      console.error(e);
      this.loadingTimed[model] = 'fail';
    }

    m.redraw();
  }

  className() {
    return 'StatisticsWidget';
  }

  content() {
    const loadingSelectedEntity = this.loadingTimed[this.selectedEntity] !== 'loaded';

    const thisPeriod = loadingSelectedEntity ? null : this.periods![this.selectedPeriod!];

    if (!this.timedData[this.selectedEntity] && this.loadingTimed[this.selectedEntity] === 'unloaded') {
      this.loadTimedData(this.selectedEntity);
    }

    return (
      <div className="StatisticsWidget-table">
        <div className="StatisticsWidget-entities">
          <div className="StatisticsWidget-labels">
            <div className="StatisticsWidget-label">{app.translator.trans('flarum-statistics.admin.statistics.total_label')}</div>
            <div className="StatisticsWidget-label">
              {loadingSelectedEntity ? (
                <LoadingIndicator size="small" display="inline" />
              ) : (
                <SelectDropdown disabled={loadingSelectedEntity} buttonClassName="Button Button--text" caretIcon="fas fa-caret-down">
                  {Object.keys(this.periods!).map((period) => (
                    <Button
                      key={period}
                      active={period === this.selectedPeriod}
                      onclick={this.changePeriod.bind(this, period)}
                      icon={period === this.selectedPeriod ? 'fas fa-check' : true}
                    >
                      {app.translator.trans(`flarum-statistics.admin.statistics.${period}_label`)}
                    </Button>
                  ))}
                </SelectDropdown>
              )}
            </div>
          </div>

          {this.entities.map((entity) => {
            const totalCount = this.loadingLifetime ? app.translator.trans('flarum-statistics.admin.statistics.loading') : this.getTotalCount(entity);
            const thisPeriodCount = loadingSelectedEntity
              ? app.translator.trans('flarum-statistics.admin.statistics.loading')
              : this.getPeriodCount(entity, thisPeriod!);
            const lastPeriodCount = loadingSelectedEntity
              ? app.translator.trans('flarum-statistics.admin.statistics.loading')
              : this.getPeriodCount(entity, this.getLastPeriod(thisPeriod!));
            const periodChange =
              loadingSelectedEntity || lastPeriodCount === 0
                ? 0
                : (((thisPeriodCount as number) - (lastPeriodCount as number)) / (lastPeriodCount as number)) * 100;

            return (
              <button
                className={'Button--ua-reset StatisticsWidget-entity' + (this.selectedEntity === entity ? ' active' : '')}
                onclick={this.changeEntity.bind(this, entity)}
              >
                <h3 className="StatisticsWidget-heading">{app.translator.trans('flarum-statistics.admin.statistics.' + entity + '_heading')}</h3>
                <div className="StatisticsWidget-total" title={totalCount}>
                  {this.loadingLifetime ? <LoadingIndicator display="inline" /> : abbreviateNumber(totalCount as number)}
                </div>
                <div className="StatisticsWidget-period" title={thisPeriodCount}>
                  {loadingSelectedEntity ? <LoadingIndicator display="inline" /> : abbreviateNumber(thisPeriodCount as number)}
                  {periodChange !== 0 && (
                    <>
                      {' '}
                      <span className={'StatisticsWidget-change StatisticsWidget-change--' + (periodChange > 0 ? 'up' : 'down')}>
                        {icon('fas fa-arrow-' + (periodChange > 0 ? 'up' : 'down'))}
                        {Math.abs(periodChange).toFixed(1)}%
                      </span>
                    </>
                  )}
                </div>
              </button>
            );
          })}
        </div>

        <>
          {loadingSelectedEntity ? (
            <div key="loading" className="StatisticsWidget-chart" data-loading="true">
              <LoadingIndicator size="large" />
            </div>
          ) : (
            <div
              key="loaded"
              className="StatisticsWidget-chart"
              data-loading="false"
              oncreate={this.drawChart.bind(this)}
              onupdate={this.drawChart.bind(this)}
            />
          )}
        </>
      </div>
    );
  }

  drawChart(vnode: Mithril.VnodeDOM<any, any>) {
    if (this.chart && this.chartEntity === this.selectedEntity && this.chartPeriod === this.selectedPeriod) {
      return;
    }

    const period = this.periods![this.selectedPeriod!];
    const periodLength = period.end - period.start;
    const labels = [];
    const thisPeriod = [];
    const lastPeriod = [];

    for (let i = period.start; i < period.end; i += period.step) {
      let label;

      if (period.step < 86400) {
        label = dayjs.unix(i).format('h A');
      } else {
        label = dayjs.unix(i).format('D MMM');

        if (period.step > 86400) {
          label += ' - ' + dayjs.unix(i + period.step - 1).format('D MMM');
        }
      }

      labels.push(label);

      thisPeriod.push(this.getPeriodCount(this.selectedEntity, { start: i, end: i + period.step }));
      lastPeriod.push(this.getPeriodCount(this.selectedEntity, { start: i - periodLength, end: i - periodLength + period.step }));
    }

    const datasets = [{ values: lastPeriod }, { values: thisPeriod }];
    const data = {
      labels,
      datasets,
    };

    // If the dom element no longer exists, recreate the chart
    // https://stackoverflow.com/a/2620373/11091039
    if (!this.chart || !(document.compareDocumentPosition(this.chart.parent) & 16)) {
      this.chart = new Chart(vnode.dom, {
        data,
        type: 'line',
        height: 280,
        axisOptions: {
          xAxisMode: 'tick',
          yAxisMode: 'span',
          xIsSeries: true,
        },
        lineOptions: {
          hideDots: 1,
        },
        colors: ['black', app.forum.attribute('themePrimaryColor')],
      });
    } else {
      this.chart.update(data);
    }

    this.chartEntity = this.selectedEntity;
    this.chartPeriod = this.selectedPeriod;
  }

  changeEntity(entity: string) {
    this.selectedEntity = entity;
  }

  changePeriod(period: string) {
    this.selectedPeriod = period;
  }

  getTotalCount(entity: string): number {
    return this.lifetimeData[entity];
  }

  getPeriodCount(entity: string, period: { start: number; end: number }) {
    const timed: Record<string, number> = this.timedData[entity];
    let count = 0;

    for (const t in timed) {
      const time = parseInt(t);

      if (time >= period.start && time < period.end) {
        count += timed[time];
      }
    }

    return count;
  }

  getLastPeriod(thisPeriod: { start: number; end: number }) {
    return {
      start: thisPeriod.start - (thisPeriod.end - thisPeriod.start),
      end: thisPeriod.start,
    };
  }
}
