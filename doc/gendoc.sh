#!/bin/bash
LANGS="en ru de" #  ru de
DEST=./build/

for lang in $LANGS
do
    echo "* generating $lang"
    make -C $lang
done

for lang in $LANGS
do
    if test ! -d $DEST/$lang
    then
        echo "* creating $DEST/$lang"
        mkdir $DEST/$lang
    fi
    if test ! -d $DEST/$lang/split
    then
        echo "* creating $DEST/$lang"
        mkdir $DEST/$lang/split
    fi

    cp *.css $DEST/$lang/
    cp *.css $DEST/$lang/split/

    cp $lang/plaintext/book.txt $DEST/$lang/phptal.txt
    cp $lang/xhtml/* $DEST/$lang/split/
    cp $lang/xhtmlonepage/book.html $DEST/$lang/index.html
done
