#!/bin/bash

downloadUrl=$(echo "$1" | base64 -d)

downloadCacheFolder='/usr/share/'$2'-download-cache'

if [ ! -d "$downloadCacheFolder" ]; then
	mkdir "$downloadCacheFolder"
fi

cd "$downloadCacheFolder" || exit

zipDownloadedFile=$2'-app-templates-cache.zip';

echo 'Download templates from url...'
wget "$downloadUrl" -O "$zipDownloadedFile"

# Unzip selected version
echo 'Unzip file...'
unzip "$zipDownloadedFile" -d templates > unziping.log

if [ ! -d '/usr/share/'"$2" ]; then
	echo 'First you need to download the app.'
	exit
fi

echo 'Rsync files with /usr/share/'"$2"'/latest'
rsync -a templates/userfiles /usr/share/"$2"/latest
rm -rf templates

chmod 755 -R /usr/share/"$2"/latest/userfiles

echo "Done!"