import Component from '../Component';

export default class Navigation extends Component {
    view(vnode) {
        return (
            <header id="header" className="App-header">
                <div id="header-navigation" className="Header-navigation"></div>
                <div className="container">
                    <h1 className="Header-title">
                        <a href="/">{vnode.attrs.title || '[TITLE]'}</a>
                    </h1>
                    <div id="header-primary" className="Header-primary"></div>
                    <div id="header-secondary" className="Header-secondary"></div>
                </div>
            </header>
        );
    }
}
