import Badge from './Badge';
import extract from '../utils/extract';

export default class GroupBadge extends Badge {
    static initProps(props) {
        super.initProps(props);

        const group = extract(props, 'group');

        if (group) {
            props.icon = group.icon();
            props.style = { backgroundColor: group.color() };
            props.label = typeof props.label === 'undefined' ? group.nameSingular() : props.label;
            props.type = `group--${group.id()}`;
        }
    }
}
