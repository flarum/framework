/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import DashboardWidget from 'flarum/components/DashboardWidget';
import SelectDropdown from 'flarum/components/SelectDropdown';
import Button from 'flarum/components/Button';
import icon from 'flarum/helpers/icon';
import listItems from 'flarum/helpers/listItems';
import ItemList from 'flarum/utils/ItemList';
import abbreviateNumber from 'flarum/utils/abbreviateNumber';

export default class StatisticsWidget extends DashboardWidget {
  init() {
    super.init();

    const today = new Date().setHours(0, 0, 0, 0) / 1000;

    this.entities = ['users', 'discussions', 'posts'];
    this.periods = {
      today: {start: today, end: today + 86400, step: 3600},
      last_7_days: {start: today - 86400 * 7, end: today, step: 86400},
      last_28_days: {start: today - 86400 * 28, end: today, step: 86400},
      last_12_months: {start: today - 86400 * 364, end: today, step: 86400 * 7}
    };

    this.selectedEntity = 'users';
    this.selectedPeriod = 'last_7_days';
  }

  className() {
    return 'StatisticsWidget';
  }

  content() {
    const thisPeriod = this.periods[this.selectedPeriod];

    return (
      <div className="StatisticsWidget-table">
        <div className="StatisticsWidget-labels">
          <div className="StatisticsWidget-label">{app.translator.trans('flarum-statistics.admin.statistics.total_label')}</div>
          <div className="StatisticsWidget-label">
            <SelectDropdown buttonClassName="Button Button--text" caretIcon="caret-down">
              {Object.keys(this.periods).map(period => (
                <Button
                  active={period === this.selectedPeriod}
                  onclick={this.changePeriod.bind(this, period)}
                  icon={period === this.selectedPeriod ? 'check' : true}>
                  {app.translator.trans('flarum-statistics.admin.statistics.'+period+'_label')}
                </Button>
              ))}
            </SelectDropdown>
          </div>
        </div>

        {this.entities.map(entity => {
          const totalCount = this.getTotalCount(entity);
          const thisPeriodCount = this.getPeriodCount(entity, thisPeriod);
          const lastPeriodCount = this.getPeriodCount(entity, this.getLastPeriod(thisPeriod));
          const periodChange = lastPeriodCount > 0 && (thisPeriodCount - lastPeriodCount) / lastPeriodCount * 100;

          return (
            <a className={'StatisticsWidget-entity'+(this.selectedEntity === entity ? ' active' : '')} onclick={this.changeEntity.bind(this, entity)}>
              <h3 className="StatisticsWidget-heading">{app.translator.trans('flarum-statistics.admin.statistics.'+entity+'_heading')}</h3>
              <div className="StatisticsWidget-total" title={totalCount}>{abbreviateNumber(totalCount)}</div>
              <div className="StatisticsWidget-period" title={thisPeriodCount}>
                {abbreviateNumber(thisPeriodCount)}{' '}
                {periodChange ? (
                  <span className={'StatisticsWidget-change StatisticsWidget-change--'+(periodChange > 0 ? 'up' : 'down')}>
                    {icon('arrow-'+(periodChange > 0 ? 'up' : 'down'))}
                    {Math.abs(periodChange.toFixed(1))}%
                  </span>
                ) : ''}
              </div>
            </a>
          );
        })}

        <div className="StatisticsWidget-chart" config={this.drawChart.bind(this)}/>
      </div>
    );
  }

  drawChart(elm, isInitialized, context) {
    if (context.chart && context.entity === this.selectedEntity && context.period === this.selectedPeriod) {
      return;
    }

    const period = this.periods[this.selectedPeriod];
    const periodLength = period.end - period.start;
    const labels = [];
    const thisPeriod = [];
    const lastPeriod = [];

    for (let i = period.start; i < period.end; i += period.step) {
      let label;

      if (period.step < 86400) {
        label = moment.unix(i).format('h A');
      } else {
        label = moment.unix(i).format('D MMM');

        if (period.step > 86400) {
          label += ' - ' + moment.unix(i + period.step - 1).format('D MMM');
        }
      }

      labels.push(label);

      thisPeriod.push(this.getPeriodCount(this.selectedEntity, {start: i, end: i + period.step}));

      lastPeriod.push(this.getPeriodCount(this.selectedEntity, {start: i - periodLength, end: i - periodLength + period.step}));
    }

    const datasets = [
      {values: lastPeriod},
      {values: thisPeriod}
    ];

    if (!context.chart) {
      context.chart = new Chart({
        parent: elm,
        data: {labels, datasets},
        type: 'line',
        height: 200,
        x_axis_mode: 'tick',
        y_axis_mode: 'span',
        is_series: 1,
        show_dots: 0,
        colors: ['rgba(0, 0, 0, 0.1)', app.forum.attribute('themePrimaryColor')]
      });
    } else {
      context.chart.update_values(datasets, labels);
    }

    context.entity = this.selectedEntity;
    context.period = this.selectedPeriod;
  }

  changeEntity(entity) {
    this.selectedEntity = entity;
  }

  changePeriod(period) {
    this.selectedPeriod = period;
  }

  getTotalCount(entity) {
    return app.data.statistics[entity].total;
  }

  getPeriodCount(entity, period) {
    const daily = app.data.statistics[entity].daily;
    let count = 0;

    for (const day in daily) {
      if (day >= period.start && day < period.end) {
        count += daily[day];
      }
    }

    return count;
  }

  getLastPeriod(thisPeriod) {
    return {
      start: thisPeriod.start - (thisPeriod.end - thisPeriod.start),
      end: thisPeriod.start
    };
  }
}
