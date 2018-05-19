# Usage

Specify the commands to be run in `.travis.yml`.

Here’s an example that runs `npm run build` against the `master`
branch whenever Travis CI completes a run, after which, the content
from the resulting `dist` directory gets pushed to the `gh-pages`
branch:

```yml
after_success:
-|

    # Add the SSH-related commands here, see:
    # https://github.com/alrra/travis-scripts/blob/master/doc/github-deploy-keys.md#26-set-up-ssh-connection-for-travis-ci

    $(npm bin)/update-branch --commands "npm run build"
                             --commit-message "Hey GitHub, this content is for you! [skip ci]"
                             --directory "dist"
                             --distribution-branch "gh-pages"
                             --source-branch "master"
```

--

<div align="center">
    <a href="github-deploy-keys.md">← previous step</a> |
    <a href="../README.md#usage">table of contents</a> |
    <a href="handle-multiple-jobs.md">next step →</a>
</div>
