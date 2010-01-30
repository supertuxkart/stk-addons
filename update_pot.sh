mkdir pot
cp *.php pot
cp include/*.php pot
cd pot
xgettext --language=php * --output=../translations.pot
