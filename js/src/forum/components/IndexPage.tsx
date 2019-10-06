import Component from '../../common/Component';

export default class IndexPage extends Component {
    oninit() {
        console.log('IndexPage#oninit');
    }

    view() {
        return (
            <div class="container">
                <h1>hi</h1>
            </div>
        );
    }
}
