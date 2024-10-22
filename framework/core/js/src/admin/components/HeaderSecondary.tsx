import app from '../app';
import Component from '../../common/Component';
import LinkButton from '../../common/components/LinkButton';
import SessionDropdown from './SessionDropdown';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import type Mithril from 'mithril';
import Search from './Search';

/**
 * The `HeaderSecondary` component displays secondary header controls.
 */
export default class HeaderSecondary extends Component {
  view() {
    return <ul className="Header-controls">{listItems(this.items().toArray())}</ul>;
  }

  /**
   * Build an item list for the controls.
   */
  items() {
    const items = new ItemList<Mithril.Children>();

    items.add('search', <Search state={app.search.state} />, 30);

    items.add(
      'help',
      <LinkButton
        className="Button Button--link"
        href="https://docs.flarum.org/troubleshoot/"
        icon="fas fa-question-circle"
        external={true}
        target="_blank"
      >
        {app.translator.trans('core.admin.header.get_help')}
      </LinkButton>
    );

    items.add('session', <SessionDropdown />);

    return items;
  }
}
