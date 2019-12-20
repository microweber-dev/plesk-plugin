#!/bin/bash -e

sudo -u $1 bash -c "ln -s $2 $3"

chown -h $1:psacln $3
