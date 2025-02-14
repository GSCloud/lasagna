#!/bin/bash
#@author Fred Brooker <git@gscloud.cz>

INPUT=logo.png
OUT_DIR=.

if ! [ -x "$(command -v convert)" ]; then
  echo "ERROR: convert command not found" >&2
  exit 1
fi

if [ -f $INPUT ]; then
  convert -flatten -background none -resize 512x512 $INPUT $OUT_DIR/logo.webp
  SIZES=(16 24 29 32 40 48 57 58 60 64 70 72 76 80 87 96 114 120 128 144 150 152 167 180 192 196 256 310 320 384 512)
  for size in ${SIZES[@]}; do
    convert -flatten -background black -resize ${size}x${size}^ $INPUT $OUT_DIR/favicon-${size}.png
    convert -flatten -background black -resize ${size}x${size}^ $INPUT $OUT_DIR/favicon-${size}.webp
    if [ -f favicon-${size}.png ]; then
      echo -ne "."
    else
      echo "ERROR: could not process input file $INPUT" >&2
      exit 1
    fi
  done
else
  echo "ERROR: input file $INPUT does not exist" >&2
  exit 1
fi

echo -en "\nDone.\n"
