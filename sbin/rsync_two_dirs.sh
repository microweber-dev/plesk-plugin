#!/bin/bash

if [ -d "$2" ]; then
	echo 'Rsync' "$2" 'to' "$3"
	sudo -u $1 bash -c "rsync -a $2 $3"
fi