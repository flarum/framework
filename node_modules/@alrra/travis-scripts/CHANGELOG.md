## 3.0.1 (April 30, 2016)

##### Improvement

* [[`a0e6f8317d`](https://github.com/alrra/travis-scripts/commit/a0e6f8317d2d686963ba6b85f65243df0673ac0a)] -
  Update the list of files to be included by `npm`.


## 3.0.0 (April 30, 2016)

##### Breaking changes

* [[`dab509dcbd`](https://github.com/alrra/travis-scripts/commit/dab509dcbdc10434e4ea1c8d360c9e365a926514)] -
  Remove support for GitHub access tokens
  (see also: [`#25`](https://github.com/alrra/travis-scripts/issues/25)).
* [[`bbd18ad347`](https://github.com/alrra/travis-scripts/commit/bbd18ad34747299aacaa178a9ef8ff50c8f6c629)] -
  Use a single character for the short command-line options
  (see also: [`#23`](https://github.com/alrra/travis-scripts/issues/23)).

##### Bug fix

* [[`197c1a78e7`](https://github.com/alrra/travis-scripts/commit/197c1a78e78306fd7ce735ecb989a0833a3b9700)] -
  Change `update-branch.sh` to allow `master` as the distribution branch
  (see also: [`#22`](https://github.com/alrra/travis-scripts/issues/22)).

##### Improvements

* [[`5464e442c0`](https://github.com/alrra/travis-scripts/commit/5464e442c02e9bc54d2fa70c5bbf712ca42707ec)] -
  Make `update-branch.sh`'s `-d/--directory` option optional.
* [[`def34123a9`](https://github.com/alrra/travis-scripts/commit/def34123a988e8925ddaeff2428f5e9f83b65b06)] -
  Make log output include name of script being executed.
  (see also: [`comment#17144757`](https://github.com/alrra/travis-scripts/commit/def34123a988e8925ddaeff2428f5e9f83b65b06#commitcomment-17144757)).
* Make various documentation related improvements
  (see also: [`#20`](https://github.com/alrra/travis-scripts/issues/20)).


## 2.1.0 (March 28, 2016)

##### Improvements

* [[`850318d739`](https://github.com/alrra/travis-scripts/commit/850318d7399b2813946e31bd9501489285284515)] -
  Improve log output
  (see also: [`a62dbcdacf`](https://github.com/alrra/travis-scripts/commit/a62dbcdacfc5ee39ce4077ed43b9a911760cc6b8)).
* Make various documentation related improvements.

##### New feature

* [[`8dca462a35`](https://github.com/alrra/travis-scripts/commit/8dca462a3501060b7d75d3f91dc425a9f51dc693)] -
  Add `set-up-ssh` helper script
  (see also: [`#19`](https://github.com/alrra/travis-scripts/issues/19)).


## 2.0.0 (February 9, 2016)

##### Breaking change

* [[`9e81ebdb77`](https://github.com/alrra/travis-scripts/commit/9e81ebdb773f720023f124e5b8b5ae750708f8ec)] -
  Discontinue executing `travis-after-all` internally.

##### New feature

* [[`b52ca7552b`](https://github.com/alrra/travis-scripts/commit/b52ca7552bfc6bee8b713fb9a6ae79f94b87068d)] -
  Make scripts also work with `Deploy Keys`.


## 1.2.2 (January 19, 2016)

##### Improvement

* [[`4ee8e07787`](https://github.com/alrra/travis-scripts/commit/4ee8e0778799cc49bc5b2fd46672fef158f09df4)] -
  Improve usage instructions from `README.md`.


## 1.2.1 (January 19, 2016)

##### Improvement

* [[`e11dabc3a5`](https://github.com/alrra/travis-scripts/commit/e11dabc3a5a7371fbe8d1bf6ae70a094b55c0212)] -
  Fix `npm` install instruction from `README.md`.


## 1.2.0 (January 19, 2016)

##### Improvements

* [[`bbf3350b4e`](https://github.com/alrra/travis-scripts/commit/bbf3350b4edb8ac2eac0e443a24fef9f63c7d586)] -
  Add usage instructions in `README.md`
  (see also: [`#17`](https://github.com/alrra/travis-scripts/issues/17)).
* [[`932c35b236`](https://github.com/alrra/travis-scripts/commit/932c35b2364ebda17c65f6e358a41d41334598c0)] -
  Make minor improvements.


## 1.1.3 (January 10, 2016)

##### Improvement

* [[`44ac04d39`](https://github.com/alrra/travis-scripts/commit/44ac04d39b835e50c7ceb976b54512d688a97e45)] -
  Use `https://` where possible.


## 1.1.2 (November 8, 2015)

##### Improvement

* [[`509e9ef526`](https://github.com/alrra/travis-scripts/commit/509e9ef5260b8120a11a8aef8b31e30cb99601fa)] -
  Update [`travis-after-all`](https://github.com/alrra/travis-after-all) to `v1.4.4`.


## 1.1.1 (September 20, 2015)

##### Improvement

* [[`1bdd08055f`](https://github.com/alrra/travis-scripts/commit/1bdd08055f60b81ce148ccd7464c9022420c057a)] -
  Update [`travis-after-all`](https://github.com/alrra/travis-after-all) to `v1.4.3`.


## 1.1.0 (September 18, 2015)

##### New Feature

* [[`dfa7d10049`](https://github.com/alrra/travis-scripts/commit/dfa7d10049ce63b87a33c2fbee93cbff62795a1c)] -
  Make scripts immediately stop if something failed.


## 1.0.0 (September 17, 2015)

##### Breaking changes

* [[`e6ad0cb681`](https://github.com/alrra/travis-scripts/commit/e6ad0cb681c03c12df2092ab86d4187d6c080f70)] -
  Remove all the default option values from scripts.
* [[`727433c628`](https://github.com/alrra/travis-scripts/commit/727433c628f25fdda094bc31b655aa889fd7079a)] -
  Move scripts into `bin/`.
* [[`69847ada77`](https://github.com/alrra/travis-scripts/commit/69847ada77dd76a1bf4e00c6bd5e594f65e80b0b)] -
  Rename script files to have more generic names.

##### Bug fix

* [[`2861a1dde5`](https://github.com/alrra/travis-scripts/commit/2861a1dde5489211e3a08d325f2461654330a7c1)] -
  Make `commit-changes.sh` push to the specified branch.

##### Improvements

* [[`b06c39b29f`](https://github.com/alrra/travis-scripts/commit/b06c39b29f1f330cd68f2f3b7bd231edf1ab4ee4)] -
  Update [`travis-after-all`](https://github.com/alrra/travis-after-all) to `v1.4.2`.
* [[`0947d00d8b`](https://github.com/alrra/travis-scripts/commit/0947d00d8b3775f926e5a0c70b901b2efce91f7d)] -
  Make scripts show all the error messages.

##### New Features

* [[`5554b9a3fc`](https://github.com/alrra/travis-scripts/commit/5554b9a3fc6b09b37df7a95b40438efe08148eb6)] -
  Make `update-branch.sh` allow even a non-root directory to be specified.
* [[`2d5dd96e6e`](https://github.com/alrra/travis-scripts/commit/2d5dd96e6ec1190c6963f30a9e780e98fb1e5052)] -
  Make scripts use [`travis-after-all`](https://github.com/alrra/travis-after-all).


## 0.6.1 (December 10, 2014)

##### Bug fix

* [[`17ae1cfbc0`](https://github.com/alrra/travis-scripts/commit/17ae1cfbc01ea0ca80b209a9d251e954d1a67c19)] -
  Fix wrong option name in the help message from `update_site_branch.sh`
  (see also : [`#4`](https://github.com/alrra/travis-scripts/issues/4)).


## 0.6.0 (December 8, 2014)

##### Breaking change

* [[`5c8c6452c81`](https://github.com/alrra/travis-scripts/commit/5c8c6452c81b894bcdc5a232ebef02c8220b5294)] -
  Allow users to specify the commands that will be executed
  (see also: [`#3`](https://github.com/alrra/travis-scripts/issues/3)).


## 0.5.1 (December 3, 2014)

##### Improvement

* [[`b76ac792b49`](https://github.com/alrra/travis-scripts/commit/b76ac792b49580cc0b3451480e3858e5317b9eec)] -
  Fix typo in `update_site_branch.sh`
  (see also: [`#2`](https://github.com/alrra/travis-scripts/issues/2)).


## 0.5.0 (November 29, 2014)

##### Breaking change

* [[`14ce4253920`](https://github.com/alrra/travis-scripts/commit/14ce42539205135389a7ea555f4a624a9a505878)] -
  Allow users to specify the default branch name
  (see also: [`#1`](https://github.com/alrra/travis-scripts/issues/1)).


## 0.4.0 (November 8, 2014)

##### Breaking change

* [[`f41f5abe98`](https://github.com/alrra/travis-scripts/commit/f41f5abe982971342fa9b1de6fee4cdc58a28b7d)] -
  Make scripts check if the pull request is targeting the `master` branch.


## 0.3.0 (November 8, 2014)

##### Breaking change

* [[`fb54392f89`](https://github.com/alrra/travis-scripts/commit/fb54392f89d99a7dcc4bf268580cf28bbc59fcb9)] -
  Make `update_site_branch.sh` remove the `.travis.yml` file.


## 0.2.2 (November 7, 2014)

##### Improvement

* [[`26dcf013a2`](https://github.com/alrra/travis-scripts/commit/26dcf013a24e6a99e8d057939915e98d04f70ffe)] -
  Use better output message in `commit_build_changes.sh`.


## 0.2.1 (November 7, 2014)

##### Improvement

* [[`f876d6e060`](https://github.com/alrra/travis-scripts/commit/f876d6e0605e66fa494b40c3908f8b468088e8c8)] -
  Make scripts only output the strictly necessary content.


## 0.2.0 (November 6, 2014)

##### Breaking changes

* [[`b5ecd3196e`](https://github.com/alrra/travis-scripts/commit/b5ecd3196e43001719461ad2a4f945972d789f2f)] -
  Make `update_site_branch.sh` more generic.
* [[`3799852850`](https://github.com/alrra/travis-scripts/commit/3799852850e3790984f780252d4143aeda2ed127)] -
  Rename `update_server_branch.sh` to `update_site_branch.sh`.


## 0.1.0 (November 5, 2014)
