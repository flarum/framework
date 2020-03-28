import Component, { ComponentProps } from '../../common/Component';
import Checkbox from '../../common/components/Checkbox';
import icon from '../../common/helpers/icon';
import ItemList from '../../common/utils/ItemList';
import User from '../../common/models/User';

export interface NotificationGridProps extends ComponentProps {
    user: User;
}

export type NotificationItem = {
    /**
     * The name of the notification type/method.
     */
    name: string;

    /**
     * The icon to display in the column header/notification grid row.
     */
    icon: string;

    /**
     * The label to display in the column header/notification grid row.
     */
    label: string;
};

/**
 * The `NotificationGrid` component displays a table of notification types and
 * methods, allowing the user to toggle each combination.
 */
export default class NotificationGrid extends Component<NotificationGridProps> {
    /**
     * Information about the available notification methods.
     */
    methods = this.notificationMethods().toArray();

    /**
     * A map of notification type-method combinations to the checkbox instances
     * that represent them.
     */
    inputs = {};

    /**
     * Information about the available notification types.
     */
    types = this.notificationTypes().toArray();

    oninit(vnode) {
        super.oninit(vnode);

        // For each of the notification type-method combinations, create and store a
        // new checkbox component instance, which we will render in the view.
        this.types.forEach((type) =>
            this.methods.forEach((method) => {
                const key = this.preferenceKey(type.name, method.name);
                const preference = this.props.user.preferences()[key];

                this.inputs[key] = new Checkbox({
                    state: !!preference,
                    disabled: typeof preference === 'undefined',
                    onchange: () => this.toggle([key]),
                    oninit: (vnode) => (this.inputs[key] = vnode.state),
                });
            })
        );
    }

    view() {
        return (
            <table className="NotificationGrid">
                <thead>
                    <tr>
                        <td />
                        {this.methods.map((method) => (
                            <th className="NotificationGrid-groupToggle" onclick={this.toggleMethod.bind(this, method.name)}>
                                {icon(method.icon)} {method.label}
                            </th>
                        ))}
                    </tr>
                </thead>

                <tbody>
                    {this.types.map((type) => (
                        <tr>
                            <td className="NotificationGrid-groupToggle" onclick={this.toggleType.bind(this, type.name)}>
                                {icon(type.icon)} {type.label}
                            </td>
                            {this.methods.map((method) => (
                                <td className="NotificationGrid-checkbox">{this.inputs[this.preferenceKey(type.name, method.name)].render()}</td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        );
    }

    oncreate(vnode) {
        super.oncreate(vnode);

        this.$('thead .NotificationGrid-groupToggle').bind('mouseenter mouseleave', function (e) {
            const i = parseInt($(this).index(), 10) + 1;
            $(this)
                .parents('table')
                .find('td:nth-child(' + i + ')')
                .toggleClass('highlighted', e.type === 'mouseenter');
        });

        this.$('tbody .NotificationGrid-groupToggle').bind('mouseenter mouseleave', function (e) {
            $(this)
                .parent()
                .find('td')
                .toggleClass('highlighted', e.type === 'mouseenter');
        });
    }

    /**
     * Toggle the state of the given preferences, based on the value of the first
     * one.
     */
    toggle(keys: string[]) {
        const user = this.props.user;
        const preferences = user.preferences();
        const enabled = !preferences[keys[0]];

        keys.forEach((key) => {
            const control = this.inputs[key];

            control.loading = true;
            control.props.state = enabled;
            preferences[key] = control.props.state = enabled;
        });

        m.redraw();

        user.save({ preferences }).then(() => {
            keys.forEach((key) => (this.inputs[key].loading = false));

            m.redraw();
        });
    }

    /**
     * Toggle all notification types for the given method.
     */
    toggleMethod(method: string) {
        const keys = this.types.map((type) => this.preferenceKey(type.name, method)).filter((key) => !this.inputs[key].props.disabled);

        this.toggle(keys);
    }

    /**
     * Toggle all notification methods for the given type.
     */
    toggleType(type: string) {
        const keys = this.methods.map((method) => this.preferenceKey(type, method.name)).filter((key) => !this.inputs[key].props.disabled);

        this.toggle(keys);
    }

    /**
     * Get the name of the preference key for the given notification type-method
     * combination.
     */
    preferenceKey(type: string, method: string): string {
        return `notify_${type}_${method}`;
    }

    /**
     * Build an item list for the notification methods to display in the grid.
     *
     * @see {NotificationItem}
     */
    notificationMethods() {
        const items = new ItemList<NotificationItem>();

        items.add('alert', {
            name: 'alert',
            icon: 'fas fa-bell',
            label: app.translator.trans('core.forum.settings.notify_by_web_heading'),
        });

        items.add('email', {
            name: 'email',
            icon: 'far fa-envelope',
            label: app.translator.trans('core.forum.settings.notify_by_email_heading'),
        });

        return items;
    }

    /**
     * Build an item list for the notification types to display in the grid.
     *
     * @see {NotificationItem}
     */
    notificationTypes() {
        const items = new ItemList<NotificationItem>();

        items.add('discussionRenamed', {
            name: 'discussionRenamed',
            icon: 'fas fa-pencil-alt',
            label: app.translator.trans('core.forum.settings.notify_discussion_renamed_label'),
        });

        return items;
    }
}
