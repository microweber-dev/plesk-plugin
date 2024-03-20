#!/bin/bash

downloadUrl=$(echo "$1" | base64 -d)

latestFolder=$2
if [ ! -d "$latestFolder" ]; then
	mkdir -p "$latestFolder"
fi

cd "$latestFolder"

zipDownloadedFile="microweber-app.zip";

echo 'Download from url...'
#wget "$downloadUrl" -O "$zipDownloadedFile"
