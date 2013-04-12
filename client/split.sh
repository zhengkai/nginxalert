#!/bin/bash
[ `id -u` -ne 0 ] && echo "Must be ROOT" && exit 1

cd "`dirname \"$0\"`"

. config.inc

tmpfile=$(mktemp)
echo $tmpfile

mv $log_path $tmpfile

cat $tmpfile

kill -USR1 `cat $pid_path`

curl -F 'upload=@'$tmpfile';type=text/plain' $upload_url

rm $tmpfile
