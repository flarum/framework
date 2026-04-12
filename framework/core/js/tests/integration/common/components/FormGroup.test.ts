import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import FormGroup from '../../../../src/common/components/FormGroup';
import m from 'mithril';
import mq from 'mithril-query';
import Stream from '../../../../src/common/utils/Stream';

beforeAll(() => bootstrapForum());

describe('FormGroup boolean/switch type', () => {
  it('renders a Switch component', () => {
    const stream = Stream(false);
    const group = mq(m(FormGroup, { type: 'bool', label: 'Enable feature', stream }));

    expect(group).toHaveElement('.Form-group');
    expect(group).toHaveElement('.Checkbox--switch');
  });

  it('renders help text when provided', () => {
    const stream = Stream(false);
    const group = mq(m(FormGroup, { type: 'bool', label: 'Enable feature', help: 'This helps you.', stream }));

    expect(group).toHaveElement('.helpText');
    expect(group).toContainRaw('This helps you.');
  });

  it('passes aria-describedby to the switch input when help text is provided', () => {
    const stream = Stream(false);
    const group = mq(m(FormGroup, { type: 'bool', label: 'Enable feature', help: 'This helps you.', stream }));

    const helpEl = group.first('.helpText') as HTMLElement;
    expect(helpEl).not.toBeNull();

    const helpId = helpEl.id;
    expect(helpId).toBeTruthy();

    expect(group).toHaveElementAttr('input[type="checkbox"]', 'aria-describedby', helpId);
  });

  it('does not add aria-describedby when no help text', () => {
    const stream = Stream(false);
    const group = mq(m(FormGroup, { type: 'bool', label: 'Enable feature', stream }));

    expect(group).not.toHaveElement('.helpText');
    // Input should not have aria-describedby (or it should be absent/empty)
    const inputEl = group.first('input[type="checkbox"]');
    expect(inputEl?.attrs?.['aria-describedby']).toBeFalsy();
  });
});
