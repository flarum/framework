import LinkButton from 'flarum/components/LinkButton';

export default class AdminLinkButton extends LinkButton {
  getButtonContent() {
    const content = super.getButtonContent();

    content.push(
      <div className="AdminLinkButton-description">
        {this.props.description}
      </div>
    );

    return content;
  }
}
