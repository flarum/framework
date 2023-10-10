import Component from '../../common/Component';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
export default class Notices extends Component {
    private loading;
    private sent;
    view(): Mithril.Children;
    items(): ItemList<Mithril.Children>;
    onclickEmailConfirmation(): void;
}
