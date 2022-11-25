#!/bin/bash

echo "move files from" $1 "to" $2

rsync -r $1 $2