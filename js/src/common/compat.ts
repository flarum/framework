import Application from './Application';
import Component from './Component';
import * as extend from './extend';
import Model from './Model';
import Session from './Session';
import Store from './Store';
import Translator from './Translator';

import Evented from './utils/Evented';
// import liveHumanTimes from './utils/liveHumanTimes';
import ItemList from './utils/ItemList';
import humanTime from './utils/humanTime';
import computed from './utils/computed';
import Drawer from './utils/Drawer';
import anchorScroll from './utils/anchorScroll';
import RequestError from './utils/RequestError';
import abbreviateNumber from './utils/abbreviateNumber';
import * as string from './utils/string';
import SubtreeRetainer from './utils/SubtreeRetainer';
import extract from './utils/extract';
import ScrollListener from './utils/ScrollListener';
import stringToColor from './utils/stringToColor';
import patchMithril from './utils/patchMithril';
import extractText from './utils/extractText';
import formatNumber from './utils/formatNumber';
import mapRoutes from './utils/mapRoutes';

import Notification from './models/Notification';
import User from './models/User';
import Post from './models/Post';
import Discussion from './models/Discussion';
import Group from './models/Group';
import Forum from './models/Forum';

import AlertManager from './components/AlertManager';
import Switch from './components/Switch';
import Badge from './components/Badge';
import LoadingIndicator from './components/LoadingIndicator';
import Placeholder from './components/Placeholder';
import Separator from './components/Separator';
import Dropdown from './components/Dropdown';
import SplitDropdown from './components/SplitDropdown';
import RequestErrorModal from './components/RequestErrorModal';
import FieldSet from './components/FieldSet';
import Select from './components/Select';
// import Navigation from './components/Navigation';
import Alert from './components/Alert';
import LinkButton from './components/LinkButton';
import Checkbox from './components/Checkbox';
import SelectDropdown from './components/SelectDropdown';
import ModalManager from './components/ModalManager';
import Button from './components/Button';
import Modal from './components/Modal';
import GroupBadge from './components/GroupBadge';

import fullTime from './helpers/fullTime';
import avatar from './helpers/avatar';
import icon from './helpers/icon';
import humanTimeHelper from './helpers/humanTime';
// import punctuateSeries from './helpers/punctuateSeries';
import highlight from './helpers/highlight';
import username from './helpers/username';
import userOnline from './helpers/userOnline';
import listItems from './helpers/listItems';

export default {
    Application: Application,
    Component: Component,
    extend: extend,
    Model: Model,
    Session: Session,
    Store: Store,
    Translator: Translator,

    'utils/Evented': Evented,
    // 'utils/liveHumanTimes': liveHumanTimes,
    'utils/ItemList': ItemList,
    'utils/humanTime': humanTime,
    'utils/computed': computed,
    'utils/Drawer': Drawer,
    'utils/anchorScroll': anchorScroll,
    'utils/RequestError': RequestError,
    'utils/abbreviateNumber': abbreviateNumber,
    'utils/string': string,
    'utils/SubtreeRetainer': SubtreeRetainer,
    'utils/extract': extract,
    'utils/ScrollListener': ScrollListener,
    'utils/stringToColor': stringToColor,
    'utils/patchMithril': patchMithril,
    'utils/extractText': extractText,
    'utils/formatNumber': formatNumber,
    'utils/mapRoutes': mapRoutes,
    'models/Notification': Notification,
    'models/User': User,
    'models/Post': Post,
    'models/Discussion': Discussion,
    'models/Group': Group,
    'models/Forum': Forum,
    'components/AlertManager': AlertManager,
    'components/Switch': Switch,
    'components/Badge': Badge,
    'components/LoadingIndicator': LoadingIndicator,
    'components/Placeholder': Placeholder,
    'components/Separator': Separator,
    'components/Dropdown': Dropdown,
    'components/SplitDropdown': SplitDropdown,
    'components/RequestErrorModal': RequestErrorModal,
    'components/FieldSet': FieldSet,
    'components/Select': Select,
    // 'components/Navigation': Navigation,
    'components/Alert': Alert,
    'components/LinkButton': LinkButton,
    'components/Checkbox': Checkbox,
    'components/SelectDropdown': SelectDropdown,
    'components/ModalManager': ModalManager,
    'components/Button': Button,
    'components/Modal': Modal,
    'components/GroupBadge': GroupBadge,

    'helpers/fullTime': fullTime,
    'helpers/avatar': avatar,
    'helpers/icon': icon,
    'helpers/humanTime': humanTimeHelper,
    // 'helpers/punctuateSeries': punctuateSeries,
    'helpers/highlight': highlight,
    'helpers/username': username,
    'helpers/userOnline': userOnline,
    'helpers/listItems': listItems,
};
