#!/bin/bash

declare -r LOG_PREFIX="[travis-scripts → $(basename "$0")]"

# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

check_if_arg_is_provided() {

    if [ -z "$1" ]; then
        print_error "ERROR: option \"$2\" not given (see --help)."
        return 1
    fi

}

execute() {
    eval ${1}
}

get_repository_url() {
    printf "git@github.com:$TRAVIS_REPO_SLUG.git"
}

print() {
    printf "%b%s%b\n" \
        "$1" \
        "${2//$'\r'//}" \
        "\e[0m"
}

print_error() {
    print_in_red "$LOG_PREFIX [✖] $1"
}

print_error_stream() {
    while read -r line; do
        print_in_red "$LOG_PREFIX [✖] $line"
    done
}

print_in_green() {
    print "\e[0;32m" "$1"
}

print_in_red() {
    print "\e[0;31m" "$1"
}

print_result() {
    [ $1 -eq 0 ] \
        && print_success "$2" \
        || print_error "$2"

    return $1
}

print_success() {
    print_in_green "$LOG_PREFIX [✔] $1"
}

remove_sensitive_information() {

    declare -r CENSOR_TEXT="[secure]";

    while IFS="" read -r line; do

        for text in "$@"; do
            line="${line//${text}/$CENSOR_TEXT}"
        done

        printf "%s\n" "$line"

    done

}
