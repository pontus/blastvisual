#!/bin/sh

infile="$1"

if [ -r "$infile" ]; then
  # Create quickload directory
  mkdirhier -p ql/${infile%/*} || true;

  cut -f 1 "$infile" | sort -u | while read p; do
    grep "^$p\s" "$infile" > "ql/$infile.$p"
  done
fi


