#!/bin/bash
rm file-list.txt
ls ../*.php >> ./file-list.txt
ls ../include/*.php >> ./file-list.txt
xgettext \
--language=php \
--keyword=_h \
--output=./translations.pot \
--msgid-bugs-address=stephenjust@users.sourceforge.net \
--add-comments=I18N \
--copyright-holder="SuperTuxKart Team" \
--package-name=stkaddons -Fn \
--files-from=file-list.txt
