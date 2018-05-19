#!/bin/bash

# Load helper functions
source "$(

    # The following is done because:
    #
    #   * `readlink` on OS X outputs a relative path
    #   * `misc.sh` is not publicly exposed
    #

    cd "$(dirname "$BASH_SOURCE")";
    cd "$(dirname $(readlink "$BASH_SOURCE"))";
    pwd

)/util/misc.sh"

declare repository_url=""

# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

commit_and_push_changes() {

    # Commit and push changes upstream, and
    # overwrite the content from the specified branch

    git config --global user.email "$GH_USER_EMAIL" \
        && git config --global user.name "$GH_USER_NAME" \
        && git init \
        && git add -A \
        && git commit --message "$2" \
        && (

            # If the distribution branch is `master`,
            # there is no need to switch as that is the default

            if [ "$1" != "master" ]; then
                git checkout --quiet -b "$1"
            fi

        ) \
        && git push --quiet --force "$repository_url" "$1"

}

print_help_message() {

    cat <<EOF

OPTIONS:

    -c, --commands <commands>

        Specifies the commands that will be executed before everything else.


    -d, --directory <directory>

        Specifies the name of the distribution/build directory.


    --distribution-branch <branch_name>

        Specifies the name of the branch on which the content resulting from running the commands will be moved on.


    -m, --commit-message <message>

        Specifies the commit message.


    --source-branch <branch_name>

        Specifies the name of the branch on which the commands are run on.

EOF

}

remove_unneeded_files() {

    local tmpDir=""

    # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    # If a directory was not specified, move to the next steps

    [ -z "$1" ] \
        && return 0

    # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    # If something other than a directory was specified, print an error

    if [ ! -d "$1" ]; then
        print_error "\"$1\" is not a directory"
        return 1
    fi

    # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    # Otherwise, remove the unneeded files and move the content
    # from within the specified directory in the root of the project

    tmpDir="$(mktemp -u /tmp/XXXXX)" \
        && cp -r "$1" "$tmpDir" \
        && find . -delete \
        && shopt -s dotglob \
        && cp -r "$tmpDir"/* . \
        && shopt -u dotglob

}

# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

main() {

    local commands=""
    local commitMessage=""
    local directory=""
    local distributionBranch=""
    local sourceBranch=""

    local allOptionsAreProvided="true"

    # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    while :; do
        case $1 in

            -h|--help)
                print_help_message
                exit
            ;;

            -c|--commands)
                if [ "$2" ]; then
                    commands="$2"
                    shift 2
                    continue
                else
                    print_error "ERROR: A non-empty \"-c/--commands <commands>\" argument needs to be specified"
                    exit 1
                fi
            ;;

            -d|--directory)
                if [ "$2" ]; then
                    directory="$2"
                    shift 2
                    continue
                else
                    print_error "ERROR: A non-empty \"-d/--directory <directory>\" argument needs to be specified"
                    exit 1
                fi
            ;;

            --distribution-branch)
                if [ "$2" ]; then
                    distributionBranch="$2"
                    shift 2
                    continue
                else
                    print_error "ERROR: A non-empty \"-db/--distribution-branch <branch_name>\" argument needs to be specified"
                    exit 1
                fi
            ;;

            -m|--commit-message)
                if [ "$2" ]; then
                    commitMessage="$2"
                    shift 2
                    continue
                else
                    echo "ERROR: A non-empty \"-m/--commit-message <message>\" argument needs to be specified" >&2
                    exit 1
                fi
            ;;

            --source-branch)
                if [ "$2" ]; then
                    sourceBranch="$2"
                    shift 2
                    continue
                else
                    echo "ERROR: A non-empty \"-sb/--source-branch <branch_name>\" argument needs to be specified" >&2
                    exit 1
                fi
            ;;

           -?*) printf "WARNING: Unknown option (ignored): %s\n" "$1" >&2;;
             *) break
        esac

        shift
    done

    # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    # Check if all the required options are provided

    (
        check_if_arg_is_provided "$commands" "-c/--commands <commands>"
        check_if_arg_is_provided "$commitMessage" "-m/--commit-message <message>"
        check_if_arg_is_provided "$distributionBranch" "--distribution-branch <branch_name>"
        check_if_arg_is_provided "$sourceBranch" "--source-branch <branch_name>"
    ) \
        || exit 1

    # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    # Only execute the following if the commit
    # was made to the specified source branch

    if [ "$TRAVIS_BRANCH" == "$sourceBranch" ] && \
       [ "$TRAVIS_PULL_REQUEST" == "false" ]; then

        repository_url="$(get_repository_url)"

        execute "$commands" \
            &> >(print_error_stream) \
            1> /dev/null
        print_result $? "Update content" \
            || exit 1

        remove_unneeded_files "$directory" \
            &> >(print_error_stream) \
            1> /dev/null
        print_result $? "Remove unneeded content" \
            || exit 1

        commit_and_push_changes "$distributionBranch" "$commitMessage" \
            &> >(print_error_stream) \
            1> /dev/null
        print_result $? "Commit and push changes"

    fi

}

main "$@" \
    &> >(remove_sensitive_information "$GH_USER_EMAIL" "$GH_USER_NAME")
