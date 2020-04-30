import Component from '../../common/Component';

export default abstract class DashboardWidget extends Component {
    view() {
        return <div className={'DashboardWidget ' + this.className()}>{this.content()}</div>;
    }

    /**
     * Get the class name to apply to the widget.
     *
     * @return {String}
     */
    className() {
        return '';
    }

    /**
     * Get the content of the widget.
     *
     * @return {VirtualElement}
     */
    abstract content(): JSX.Element;
}
