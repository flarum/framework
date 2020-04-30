import LinkButton, { LinkButtonProps } from '../../common/components/LinkButton';

interface AdminLinkButtonProps extends LinkButtonProps {
    description?: string;
}

export default class AdminLinkButton<T extends AdminLinkButtonProps = AdminLinkButtonProps> extends LinkButton<T> {
    getButtonContent() {
        const content = super.getButtonContent(this.props.icon, this.props.loading, this.props.children);

        content.push(<div className="AdminLinkButton-description">{this.props.description}</div>);

        return content;
    }
}
