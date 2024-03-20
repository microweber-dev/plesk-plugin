#!/bin/bash

downloadUrl=$(echo "$1" | base64 -d)

targetFolder=$2
if [ ! -d "$targetFolder" ]; then
	mkdir -p "$targetFolder"
fi

cd "$targetFolder"

zipDownloadedFile="$3";

echo "Download from url:" "$downloadUrl";

wget "$downloadUrl" -O "$zipDownloadedFile" 1>/dev/null 2>&1 &

echo "File is downloaded:" "$zipDownloadedFile";
