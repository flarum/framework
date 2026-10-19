import Extend from 'flarum/common/extenders';
import IdeaShippedNotification from './components/IdeaShippedNotification';
import IdeationStatusPost from './components/IdeationStatusPost';

export default [
  new Extend.Routes().add('ideation', '/roadmap', () => import('./components/IdeationPage')),

  new Extend.PostTypes().add('ideationStatus', IdeationStatusPost),

  new Extend.Notification().add('ideationShipped', IdeaShippedNotification),
];
