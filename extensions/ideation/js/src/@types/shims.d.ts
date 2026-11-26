import type { GithubLink } from '../forum/components/GithubLinks';

declare module 'flarum/common/models/Discussion' {
  export default interface Discussion {
    ideationStatus: () => string | null | undefined;
    ideationVotes: () => number | undefined;
    ideationVoted: () => boolean | undefined;
    canIdeationVote: () => boolean | undefined;
    canIdeationModerate: () => boolean | undefined;
    ideationMergedIntoId: () => number | null | undefined;
    ideationGithub: () => GithubLink[] | undefined;
  }
}
