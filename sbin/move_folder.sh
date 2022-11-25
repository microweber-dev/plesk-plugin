#!/bin/bash

echo "move files from" $1 "to" $2

rm -rf $2
mv $1 $2

