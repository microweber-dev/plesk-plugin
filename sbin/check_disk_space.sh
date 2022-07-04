#!/bin/bash

df -P $1 | awk 'NR == 2 { print $4 }'
