import User from 'flarum/common/models/User';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
export default function RealtimeUserPreferencesItems(user?: User): ItemList<Mithril.Children>;
