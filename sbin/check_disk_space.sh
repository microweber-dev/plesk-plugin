#!/bin/bash

df $1 | awk 'NR==2{print$4}'