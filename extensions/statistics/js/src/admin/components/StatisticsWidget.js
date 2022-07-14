import app from 'flarum/admin/app';
import DashboardWidget from 'flarum/admin/components/DashboardWidget';
import SelectDropdown from 'flarum/common/components/SelectDropdown';
import Button from 'flarum/common/components/Button';
import icon from 'flarum/common/helpers/icon';
// import abbreviateNumber from 'flarum/common/utils/abbreviateNumber';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

const abbreviateNumber = (x) => x;

import { Chart } from 'frappe-charts';

export default class StatisticsWidget extends DashboardWidget {
  entities = ['users', 'discussions', 'posts'];
  selectedEntity = 'users';

  timedData;
  lifetimeData;

  loadingLifetime = true;
  loadingTimed = true;

  oncreate(vnode) {
    super.oncreate(vnode);

    this.loadLifetimeData();
    this.loadTimedData();
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

  async loadTimedData() {
    this.loadingTimed = true;
    m.redraw();

    const data = await app.request({
      method: 'GET',
      url: app.forum.attribute('apiUrl') + '/statistics',
    });

    this.timedData = data;
    this.loadingTimed = false;

    // Create a Date object which represents the start of the day in the
    // configured timezone. To do this we convert a UTC time into that timezone,
    // reset to the first hour of the day, and then convert back into UTC time.
    // We'll be working with seconds rather than milliseconds throughout too.
    let today = new Date();
    today.setTime(today.getTime() + this.timedData.timezoneOffset * 1000);
    today.setUTCHours(0, 0, 0, 0);
    today.setTime(today.getTime() - this.timedData.timezoneOffset * 1000);
    today = today / 1000;

    this.periods = {
      today: { start: today, end: today + 86400, step: 3600 },
      last_7_days: { start: today - 86400 * 7, end: today, step: 86400 },
      last_28_days: { start: today - 86400 * 28, end: today, step: 86400 },
      last_12_months: { start: today - 86400 * 364, end: today, step: 86400 * 7 },
    };

    this.selectedPeriod = 'last_7_days';

    m.redraw();
  }

  className() {
    return 'StatisticsWidget';
  }

  content() {
    const thisPeriod = this.loadingTimed ? null : this.periods[this.selectedPeriod];

    return (
      <div className="StatisticsWidget-table">
        <div className="StatisticsWidget-labels">
          <div className="StatisticsWidget-label">{app.translator.trans('flarum-statistics.admin.statistics.total_label')}</div>
          <div className="StatisticsWidget-label">
            {this.loadingTimed ? (
              <LoadingIndicator size="small" display="inline" />
            ) : (
              <SelectDropdown disabled={this.loadingTimed} buttonClassName="Button Button--text" caretIcon="fas fa-caret-down">
                {Object.keys(this.periods).map((period) => (
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
          const thisPeriodCount = this.loadingTimed
            ? app.translator.trans('flarum-statistics.admin.statistics.loading')
            : this.getPeriodCount(entity, thisPeriod);
          const lastPeriodCount = this.loadingTimed
            ? app.translator.trans('flarum-statistics.admin.statistics.loading')
            : this.getPeriodCount(entity, this.getLastPeriod(thisPeriod));
          const periodChange = this.loadingTimed ? false : lastPeriodCount > 0 && ((thisPeriodCount - lastPeriodCount) / lastPeriodCount) * 100;

          return (
            <a
              className={'StatisticsWidget-entity' + (this.selectedEntity === entity ? ' active' : '')}
              onclick={this.changeEntity.bind(this, entity)}
            >
              <h3 className="StatisticsWidget-heading">{app.translator.trans('flarum-statistics.admin.statistics.' + entity + '_heading')}</h3>
              <div className="StatisticsWidget-total" title={totalCount}>
                {this.loadingLifetime ? <LoadingIndicator display="inline" /> : abbreviateNumber(totalCount)}
              </div>
              <div className="StatisticsWidget-period" title={thisPeriodCount}>
                {this.loadingTimed ? <LoadingIndicator display="inline" /> : abbreviateNumber(thisPeriodCount)}
                {!!periodChange && (
                  <>
                    {' '}
                    <span className={'StatisticsWidget-change StatisticsWidget-change--' + (periodChange > 0 ? 'up' : 'down')}>
                      {icon('fas fa-arrow-' + (periodChange > 0 ? 'up' : 'down'))}
                      {Math.abs(periodChange.toFixed(1))}%
                    </span>
                  </>
                )}
              </div>
            </a>
          );
        })}

        {this.loadingTimed ? (
          <div className="StatisticsWidget-chart">
            <LoadingIndicator size="large" />
          </div>
        ) : (
          <div className="StatisticsWidget-chart" oncreate={this.drawChart.bind(this)} onupdate={this.drawChart.bind(this)} />
        )}
      </div>
    );
  }

  drawChart(vnode) {
    if (this.chart && this.entity === this.selectedEntity && this.period === this.selectedPeriod) {
      return;
    }

    const offset = this.timedData.timezoneOffset;
    const period = this.periods[this.selectedPeriod];
    const periodLength = period.end - period.start;
    const labels = [];
    const thisPeriod = [];
    const lastPeriod = [];

    for (let i = period.start; i < period.end; i += period.step) {
      let label;

      if (period.step < 86400) {
        label = dayjs.unix(i + offset).format('h A');
      } else {
        label = dayjs.unix(i + offset).format('D MMM');

        if (period.step > 86400) {
          label += ' - ' + dayjs.unix(i + offset + period.step - 1).format('D MMM');
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

    if (!this.chart) {
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

    this.entity = this.selectedEntity;
    this.period = this.selectedPeriod;
  }

  changeEntity(entity) {
    this.selectedEntity = entity;
  }

  changePeriod(period) {
    this.selectedPeriod = period;
  }

  getTotalCount(entity) {
    return this.lifetimeData[entity];
  }

  getPeriodCount(entity, period) {
    const timed = this.timedData[entity];
    let count = 0;

    for (const time in timed) {
      if (time >= period.start && time < period.end) {
        count += parseInt(timed[time]);
      }
    }

    return count;
  }

  getLastPeriod(thisPeriod) {
    return {
      start: thisPeriod.start - (thisPeriod.end - thisPeriod.start),
      end: thisPeriod.start,
    };
  }
}
