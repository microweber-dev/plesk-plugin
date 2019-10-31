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

zipDownloadedFile="microweber-app-templates.zip";

echo 'Download modules from url...'
wget "$downloadUrl" -O "$zipDownloadedFile"

# Unzip module
unzip $2"/cache/"$zipDownloadedFile -d $2"/latest/" > unziping-microweber-app-templates.log

chmod 755 -R $2"/latest"

echo "Done!"