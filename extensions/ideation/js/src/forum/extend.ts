import Extend from 'flarum/common/extenders';
import Discussion from 'flarum/common/models/Discussion';
import IdeaShippedNotification from './components/IdeaShippedNotification';
import IdeationStatusPost from './components/IdeationStatusPost';
import type { GithubLink } from './components/GithubLinks';

export default [
  new Extend.Routes().add('ideation', '/roadmap', () => import('./components/IdeationPage')),

  new Extend.PostTypes().add('ideationStatus', IdeationStatusPost),

  new Extend.Notification().add('ideationShipped', IdeaShippedNotification),

  new Extend.Model(Discussion)
    .attribute<string | null>('ideationStatus')
    .attribute<number>('ideationVotes')
    .attribute<boolean>('ideationVoted')
    .attribute<boolean>('canIdeationVote')
    .attribute<boolean>('canIdeationModerate')
    .attribute<number | null>('ideationMergedIntoId')
    .attribute<GithubLink[]>('ideationGithub'),
];
