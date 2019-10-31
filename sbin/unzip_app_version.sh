#!/bin/bash

downloadUrl=$(echo "$1" | base64 -d)

latestFolder=$2"/latest"
if [ ! -d "$latestFolder" ]; then
	mkdir -p "$latestFolder"
fi

downloadCacheFolder=$2"/cache"
if [ ! -d "$downloadCacheFolder" ]; then
	mkdir -p "$downloadCacheFolder"
fi

cd "$downloadCacheFolder" || exit

zipDownloadedFile="microweber-app.zip";

echo 'Download from url...'
wget "$downloadUrl" -O "$zipDownloadedFile"

# Unzip selected version
echo 'Unzip file...'
unzip $2"/cache/"$zipDownloadedFile -d $2"/latest" > unziping-microweber-app.log

chmod 755 -R $2"/latest"

echo "Done!"