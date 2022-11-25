#!/bin/bash

echo "move files from" $1 "to" $2

rsync -va --delete-after $1 $2
