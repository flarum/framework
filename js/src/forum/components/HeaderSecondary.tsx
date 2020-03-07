import { Vnode } from 'mithril';

import Component from '../../common/Component';
import Button from '../../common/components/Button';
import LogInModal from './LogInModal';
// import SignUpModal from './SignUpModal';
import SessionDropdown from './SessionDropdown';
import SelectDropdown from '../../common/components/SelectDropdown';
import NotificationsDropdown from './NotificationsDropdown';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';

import Search from './Search';

/**
 * The `HeaderSecondary` component displays secondary header controls, such as
 * the search box and the user menu. On the default skin, these are shown on the
 * right side of the header.
 */
export default class HeaderSecondary extends Component {
    view() {
        return <ul className="Header-controls">{listItems(this.items().toArray())}</ul>;
    }

    /**
     * Build an item list for the controls.
     */
    items(): ItemList {
        const items = new ItemList();

        items.add('search', Search.component(), 30);

        if (app.forum.attribute('showLanguageSelector') && Object.keys(app.data.locales).length > 1) {
            const locales: Vnode<any, any>[] = [];

            for (const locale in app.data.locales) {
                if (!app.data.locales.hasOwnProperty(locale)) continue;

                locales.push(
                    Button.component({
                        active: app.data.locale === locale,
                        children: app.data.locales[locale],
                        icon: app.data.locale === locale ? 'fas fa-check' : true,
                        onclick: () => {
                            if (app.session.user) {
                                app.session.user.savePreferences({ locale }).then(() => window.location.reload());
                            } else {
                                document.cookie = `locale=${locale}; path=/; expires=Tue, 19 Jan 2038 03:14:07 GMT`;
                                window.location.reload();
                            }
                        },
                    })
                );
            }

            items.add(
                'locale',
                SelectDropdown.component({
                    children: locales,
                    buttonClassName: 'Button Button--link',
                }),
                20
            );
        }

        if (app.session.user) {
            items.add('notifications', <NotificationsDropdown />, 10);
            items.add('session', <SessionDropdown />, 0);
        } else {
            if (app.forum.attribute('allowSignUp')) {
                items.add(
                    'signUp',
                    Button.component({
                        children: app.translator.trans('core.forum.header.sign_up_link'),
                        className: 'Button Button--link',
                        onclick: () => app.modal.show(new SignUpModal()),
                    }),
                    10
                );
            }

            items.add(
                'logIn',
                Button.component({
                    children: app.translator.trans('core.forum.header.log_in_link'),
                    className: 'Button Button--link',
                    onclick: () => app.modal.show(new LogInModal()),
                }),
                0
            );
        }

        return items;
    }
}
