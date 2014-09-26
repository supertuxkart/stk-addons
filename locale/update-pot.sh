#!/bin/bash

echo "Creating php files index (file-list.txt)"
rm -f file-list.txt
ls ../*.php >> ./file-list.txt
ls ../include/*.php >> ./file-list.txt

echo "Generating from code"
xgettext \
--language=php \
--keyword=_h \
--output=./code.pot \
--msgid-bugs-address=stephenjust@users.sourceforge.net \
--add-comments=I18N \
--copyright-holder="SuperTuxKart Team" \
--package-name=stkaddons -Fn \
--files-from=file-list.txt

echo "Generating from templates"
../vendor/bin/tsmarty2c.php -o template.pot ../tpl

echo "Concatenating"
msgcat -o translations.pot code.pot template.pot

echo "Cleaning up"
rm -f code.pot template.pot

echo "DONE"
