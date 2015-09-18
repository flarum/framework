import SettingsModal from 'flarum/components/SettingsModal';

export default class TagSettingsModal extends SettingsModal {
  setMinTags(minTags, maxTags, value) {
    minTags(value);
    maxTags(Math.max(value, maxTags()));
  }

  className() {
    return 'TagSettingsModal Modal--small';
  }

  title() {
    return 'Tag Settings';
  }

  form() {
    const minPrimaryTags = this.setting('tags.min_primary_tags', 0);
    const maxPrimaryTags = this.setting('tags.max_primary_tags', 0);

    const minSecondaryTags = this.setting('tags.min_secondary_tags', 0);
    const maxSecondaryTags = this.setting('tags.max_secondary_tags', 0);

    return [
      <div className="Form-group">
        <label>Required Number of Primary Tags</label>
        <div className="helpText">
          Enter the minimum and maximum number of primary tags that may be applied to a discussion.
        </div>
        <div className="TagSettingsModal-rangeInput">
          <input className="FormControl"
            type="number"
            min="0"
            value={minPrimaryTags()}
            oninput={m.withAttr('value', this.setMinTags.bind(this, minPrimaryTags, maxPrimaryTags))} />
          {' to '}
          <input className="FormControl"
            type="number"
            min={minPrimaryTags()}
            bidi={maxPrimaryTags} />
        </div>
      </div>,

      <div className="Form-group">
        <label>Required Number of Secondary Tags</label>
        <div className="helpText">
          Enter the minimum and maximum number of secondary tags that may be applied to a discussion.
        </div>
        <div className="TagSettingsModal-rangeInput">
          <input className="FormControl"
            type="number"
            min="0"
            value={minSecondaryTags()}
            oninput={m.withAttr('value', this.setMinTags.bind(this, minSecondaryTags, maxSecondaryTags))} />
          {' to '}
          <input className="FormControl"
            type="number"
            min={minSecondaryTags()}
            bidi={maxSecondaryTags} />
        </div>
      </div>
    ];
  }
}
