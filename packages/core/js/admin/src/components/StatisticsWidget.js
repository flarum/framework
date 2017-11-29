/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import DashboardWidget from 'flarum/components/DashboardWidget';
import icon from 'flarum/helpers/icon';
import listItems from 'flarum/helpers/listItems';
import ItemList from 'flarum/utils/ItemList';

export default class StatisticsWidget extends DashboardWidget {
  className() {
    return 'StatisticsWidget';
  }

  content() {
    return (
      <table>
        <thead>
          <tr>
            <th></th>
            <th>{app.translator.trans('core.admin.statistics.users_heading')}</th>
            <th>{app.translator.trans('core.admin.statistics.discussions_heading')}</th>
            <th>{app.translator.trans('core.admin.statistics.posts_heading')}</th>
          </tr>
        </thead>
        <tbody>
          <tr className="StatisticsWidget-total">
            <th>{app.translator.trans('core.admin.statistics.total_label')}</th>
            <td>{app.data.statistics.total.users}</td>
            <td>{app.data.statistics.total.discussions}</td>
            <td>{app.data.statistics.total.posts}</td>
          </tr>
          <tr>
            <th>{app.translator.trans('core.admin.statistics.last_28_days_label')}</th>
            <td>{app.data.statistics.month.users}</td>
            <td>{app.data.statistics.month.discussions}</td>
            <td>{app.data.statistics.month.posts}</td>
          </tr>
          <tr>
            <th>{app.translator.trans('core.admin.statistics.last_7_days_label')}</th>
            <td>{app.data.statistics.week.users}</td>
            <td>{app.data.statistics.week.discussions}</td>
            <td>{app.data.statistics.week.posts}</td>
          </tr>
          <tr>
            <th>{app.translator.trans('core.admin.statistics.today_label')}</th>
            <td>{app.data.statistics.today.users}</td>
            <td>{app.data.statistics.today.discussions}</td>
            <td>{app.data.statistics.today.posts}</td>
          </tr>
        </tbody>
      </table>
    );
  }
}
