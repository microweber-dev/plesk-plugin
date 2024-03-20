#!/bin/bash

downloadUrl=$(echo "$1" | base64 -d)

targetFolder=$2
if [ ! -d "$targetFolder" ]; then
	mkdir -p "$targetFolder"
fi

cd "$targetFolder"

zipDownloadedFile="$3";

echo "Download from url:" "$downloadUrl";

#wget "$downloadUrl" -O "$zipDownloadedFile"

# Retry 100 times
Retry=0
until wget "$downloadUrl" -O "$zipDownloadedFile"; do
    printf 'DOWNLOAD....\n'
    sleep 2
    Retry=$((Retry+1))
    if [ $Retry -eq 100 ]; then
        echo "Failed to download file after 100 retries"
        exit 1
    fi
done

echo "Downloaded file:" "$zipDownloadedFile";

