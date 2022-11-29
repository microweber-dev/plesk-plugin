#!/bin/bash

echo "symlink file from" $1 "to" $2

rm -rf $2
ln -s $1 $2
