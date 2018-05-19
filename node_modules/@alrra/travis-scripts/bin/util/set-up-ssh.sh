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

)/misc.sh"

# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

add_ssh_configs() {
    chmod 600 "$1" \
        && printf "%s\n" \
            "Host github.com" \
            "  IdentityFile $1" \
            "  LogLevel ERROR" >> ~/.ssh/config
}

decrypt_private_ssh_key() {
    openssl aes-256-cbc \
        -K "$1" \
        -iv "$2" \
        -in "$(pwd)/$3" \
        -out "$4" -d
}

print_help_message() {

    cat <<EOF

OPTIONS:

    -k, --key <key_value>

        Specifies the value of the key stored in the `encrypted_XXXXXXXXXXXX_key` envirorment variable.


    -i, --iv <iv_value>

        Specifies the value of the IV stored in the `encrypted_XXXXXXXXXXXX_iv` envirorment variable.


    -p, --path-encrypted-key <path>

        Specifies the location of the encrypted private key file relative to where the script is executed from.

EOF

}

# ----------------------------------------------------------------------

main() {

    local iv=""
    local key=""
    local pathEncryptedKey=""
    local sshFileName=""

    # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    while :; do
        case $1 in

            -h|--help)
                print_help_message
                exit
            ;;

            -i|--iv)
                if [ -n "$2" ]; then
                    iv="$2"
                    shift 2
                    continue

                else
                    print_error "ERROR: A non-empty \"-i/--iv <iv_value>\" argument needs to be specified"
                    exit 1
                fi
            ;;

            -k|--key)
                if [ -n "$2" ]; then
                    key="$2"
                    shift 2
                    continue
                else
                    print_error "ERROR: A non-empty \"-k/--key <key_value>\" argument needs to be specified"
                    exit 1
                fi
            ;;

            -p|--path-encrypted-key)
                if [ -n "$2" ]; then
                    pathEncryptedKey="$2"
                    shift 2
                    continue
                else
                    print_error "ERROR: A non-empty \"-p/--path-encrypted-key <path>\" argument needs to be specified"
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
        check_if_arg_is_provided "$iv" "-i/--iv <iv_value>"
        check_if_arg_is_provided "$key" "-k/--key <key_value>"
        check_if_arg_is_provided "$pathEncryptedKey" "-p/--path-encrypted-key <path>"
    ) \
        || exit 1

    # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    sshFileName="$(mktemp -u $HOME/.ssh/XXXXX)"

    decrypt_private_ssh_key "$key" "$iv" "$pathEncryptedKey" "$sshFileName" \
        &> >(print_error_stream) \
        1> /dev/null
    print_result $? "Decrypt the file containing the private key" \
        || exit 1

    add_ssh_configs "$sshFileName" \
        &> >(print_error_stream) \
        1> /dev/null
    print_result $? "Add configs to enable SSH authentication" \
        || exit 1

    return 0

}

main "$@" \
    &> >(remove_sensitive_information "$GH_USER_EMAIL" "$GH_USER_NAME")
