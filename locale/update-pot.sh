#!/bin/bash

# make bash sane
set -eu -o pipefail
DIR=$(basename "$PWD")
if [[ "$DIR" != "locale" ]]; then
    echo "You are not inside the 'locale' directory"
    echo "ABORTING."
    exit 0
fi

echo "Creating php files index (file-list.txt)"
rm -f file-list.txt
ls ../*.php >> ./file-list.txt
ls ../include/*.php >> ./file-list.txt
ls ../stats/*.php >> ./file-list.txt
ls ../bugs/*.php >> ./file-list.txt
ls ../json/*.php >> ./file-list.txt

echo "Generating from code"
xgettext \
--language=php \
--keyword=_h \
--output=./code.pot \
--msgid-bugs-address=supertuxkart-translations@lists.sourceforge.net \
--add-comments=I18N \
--copyright-holder="SuperTuxKart Team" \
--package-name=stk-addons -Fn \
--files-from=file-list.txt

echo "Generating from templates"
../vendor/bin/tsmarty2c.php -o template.pot ../tpl

echo "Concatenating"
msgcat -o translations.pot code.pot template.pot

echo "Cleaning up"
rm -f code.pot template.pot

echo "DONE"
