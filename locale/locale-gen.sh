#!/bin/bash

# make bash sane
set -eu -o pipefail
DIR=$(basename "$PWD")
if [[ "$DIR" != "locale" ]]; then
    echo "You are not inside the 'locale' directory"
    echo "ABORTING."
    exit 0
fi

function install()
{
    if [[ ! -f "/usr/share/i18n/SUPPORTED" ]]; then
        echo "Supported locales files does not exist"
        echo "ABORTING"
        exit 0
    fi

    for dir in *; do
        if [[ -d $dir ]]; then
            local loc="$dir.UTF-8"
            if grep -q "$loc" /usr/share/i18n/SUPPORTED; then
                sudo locale-gen $loc
            else
                echo "$loc locale is NOT supported"
            fi
        fi
    done
}

echo "This script will generate all the required locales for stk-addons and install them system wide (locale-gen)"
echo ""

read -p "Are you sure you want to continue? <y/N> " prompt
if [[ $prompt == "y" || $prompt == "Y" || $prompt == "yes" || $prompt == "Yes" ]]; then
    install
else
    echo "ABORTING."
    exit 0
fi
