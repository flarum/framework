# Handle multiple jobs

If your build has multiple jobs, you will want these travis scripts
to be executed only once, and for that you will need to use
[`travis-after-all`](https://github.com/alrra/travis-after-all#readme).

 1. Install `travis-after-all` as a `devDependency`

  ```bash
  npm install --save-dev travis-after-all
  ```

 2. Update `.travis.yml` to include the `travis-after-all` execution

  ```yml
  after_success:
    -|

       # ...

       $(npm bin)/travis-after-all && \
         $(npm bin)/update-branch --commands "npm run build"
                                  --commit-message "Hey GitHub, this content is for you! [skip ci]"
                                  --directory "build"
                                  --distribution-branch "gh-pages"
                                  --source-branch "master"
  ```

--

<div align="center">
    <a href="usage.md">← previous step</a> |
    <a href="../README.md#usage">table of contents</a>
</div>
