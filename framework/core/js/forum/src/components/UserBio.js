import Component from 'flarum/Component';
import classList from 'flarum/utils/classList';

/**
 * The `UserBio` component displays a user's bio, optionally letting the user
 * edit it (if they have permission).
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
      content = <textarea className="form-control" placeholder="Write something about yourself" rows="3"/>;
    } else {
      let subContent;

      if (this.loading) {
        subContent = <p className="placeholder">Saving</p>;
      } else {
        const bioHtml = user.bioHtml();

        if (bioHtml) {
          subContent = m.trust(bioHtml);
        } else if (this.props.editable) {
          subContent = <p className="placeholder">Write something about yourself</p>;
        }
      }

      content = <div className="bio-content">{subContent}</div>;
    }

    return (
      <div className={'bio ' + classList({
          editable: this.isEditable(),
          editing: this.editing
        })}
        onclick={this.edit.bind(this)}>
        {content}
      </div>
    );
  }

  /**
   * Check whether or not the bio can be edited.
   *
   * @return {Boolean}
   */
  isEditable() {
    return this.props.user.canEdit() && this.props.editable;
  }

  /**
   * Edit the bio.
   */
  edit() {
    if (!this.isEditable()) return;

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
