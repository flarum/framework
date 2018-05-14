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

export default class StatusWidget extends DashboardWidget {
  className() {
    return 'StatusWidget';
  }

  content() {
    return (
      <ul>{listItems(this.items().toArray())}</ul>
    );
  }

  items() {
    const items = new ItemList();

    items.add('help', (
      <a href="http://flarum.org/docs/troubleshooting" target="_blank">
        {icon('fas fa-question-circle')} {app.translator.trans('core.admin.dashboard.help_link')}
      </a>
    ));

    items.add('version-flarum', [<strong>Flarum</strong>, <br/>, app.forum.attribute('version')]);
    items.add('version-php', [<strong>PHP</strong>, <br/>, app.data.phpVersion]);
    items.add('version-mysql', [<strong>MySQL</strong>, <br/>, app.data.mysqlVersion]);

    return items;
  }
}
