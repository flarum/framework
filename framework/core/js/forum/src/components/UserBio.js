import Component from 'flarum/Component';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import classList from 'flarum/utils/classList';

/**
 * The `UserBio` component displays a user's bio, optionally letting the user
 * edit it.
 */
export default class UserBio extends Component {
  constructor(...args) {
    super(...args);

    /**
     * Whether or not the bio is currently being edited.
     *
     * @type {Boolean}
     */
    this.editing = false;

    /**
     * Whether or not the bio is currently being saved.
     *
     * @type {Boolean}
     */
    this.loading = false;
  }

  view() {
    const user = this.props.user;
    let content;

    if (this.editing) {
      content = <textarea className="FormControl" placeholder={app.trans('core.bio_placeholder')} rows="3" value={user.bio()}/>;
    } else {
      let subContent;

      if (this.loading) {
        subContent = <p className="UserBio-placeholder">{LoadingIndicator.component({size: 'tiny'})}</p>;
      } else {
        const bioHtml = user.bioHtml();

        if (bioHtml) {
          subContent = m.trust(bioHtml);
        } else if (this.props.editable) {
          subContent = <p className="UserBio-placeholder">{app.trans('core.bio_placeholder')}</p>;
        }
      }

      content = <div className="UserBio-content">{subContent}</div>;
    }

    return (
      <div className={'UserBio ' + classList({
          editable: this.props.editable,
          editing: this.editing
        })}
        onclick={this.edit.bind(this)}>
        {content}
      </div>
    );
  }

  /**
   * Edit the bio.
   */
  edit() {
    if (!this.props.editable) return;

    this.editing = true;
    m.redraw();

    const bio = this;
    const save = function(e) {
      if (e.shiftKey) return;
      e.preventDefault();
      bio.save($(this).val());
    };

    this.$('textarea').focus()
      .bind('blur', save)
      .bind('keydown', 'return', save);
  }

  /**
   * Save the bio.
   *
   * @param {String} value
   */
  save(value) {
    const user = this.props.user;

    if (user.bio() !== value) {
      this.loading = true;

      user.save({bio: value}).then(() => {
        this.loading = false;
        m.redraw();
      });
    }

    this.editing = false;
    m.redraw();
  }
}
