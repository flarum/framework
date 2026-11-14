import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
export default class Footer extends Component {
    view(): JSX.Element | null;
    items(): ItemList<Mithril.Children>;
}
