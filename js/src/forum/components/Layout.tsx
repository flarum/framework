import Component from '../../common/Component';
import Navigation from '../../common/components/Navigation';

export default class Layout extends Component {
    view() {
        return <Navigation />;
        // return m(Navigation, {title: "title"}); // trying this
    }
}
