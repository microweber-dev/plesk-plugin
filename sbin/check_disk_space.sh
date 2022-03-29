#!/bin/bash

df $1 | grep sda | cut -d" " -f14