#!/bin/sh

infile="$1"

if [ -r "$1" ]; then
  # Create quickload directory
  mkdir ql || true;

  cut -f 1 "$1" | sort -u | while read p; do
    grep "^$p\s" "$1" > "ql/$1.$p"
  done
fi


