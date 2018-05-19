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

# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

commit_and_push_changes() {

    # Check if there are unstaged changes, and
    # if there are, commit and push them upstream

    if [ "$(git status --porcelain)" != "" ]; then
        git config --global user.email "$GH_USER_EMAIL" \
            && git config --global user.name "$GH_USER_NAME" \
            && git checkout --quiet "$1" \
            && git add -A \
            && git commit --message "$2" \
            && git push --quiet "$(get_repository_url)" "$1"
    fi

}

print_help_message() {

    cat <<EOF

OPTIONS:

    -b, --branch <branch_name>

        Specifies the name of the branch on which the changes are made.


    -c, --commands <commands>

        Specifies the commands that will be executed before everything else.


    -m, --commit-message <message>

        Specifies the commit message.

EOF

}

# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

main() {

    local branch=""
    local commands=""
    local commitMessage=""

    local allOptionsAreProvided="true"

    # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    while :; do
        case $1 in

            -h|--help)
                print_help_message
                exit
            ;;

            -b|--branch)
                if [ -n "$2" ]; then
                    branch="$2"
                    shift 2
                    continue
                else
                    print_error "ERROR: A non-empty \"-b/--branch <branch_name>\" argument needs to be specified"
                    exit 1
                fi
            ;;

            -c|--commands)
                if [ -n "$2" ]; then
                    commands="$2"
                    shift 2
                    continue
                else
                    print_error "ERROR: A non-empty \"-c/--commands <commands>\" argument needs to be specified"
                    exit 1
                fi
            ;;

            -m|--commit-message)
                if [ -n "$2" ]; then
                    commitMessage="$2"
                    shift 2
                    continue

                else
                    print_error "ERROR: A non-empty \"-m/--commit-message <message>\" argument needs to be specified"
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
        check_if_arg_is_provided "$branch" "-b/--branch <branch_name>"
        check_if_arg_is_provided "$commands" "-c/--commands <commands>"
        check_if_arg_is_provided "$commitMessage" "-m/--commit-message <message>"
    ) \
        || exit 1

    # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    # Only execute the following if the
    # commit was made to the specified branch

    if [ "$TRAVIS_BRANCH" == "$branch" ] && \
       [ "$TRAVIS_PULL_REQUEST" == "false" ]; then

        execute "$commands" \
            &> >(print_error_stream) \
            1> /dev/null
        print_result $? "Update content" \
            || exit 1

        commit_and_push_changes "$branch" "$commitMessage" \
            &> >(print_error_stream) \
            1> /dev/null
        print_result $? "Commit and push changes (if necessary)"

    fi

}

main "$@" \
    &> >(remove_sensitive_information "$GH_USER_EMAIL" "$GH_USER_NAME")
