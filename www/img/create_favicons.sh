#!/bin/bash

INPUT_IMAGE="$1"
OUTPUT_DIR="$2"

if [ -z "$INPUT_IMAGE" ]; then INPUT_IMAGE="logo.png"; fi
if [ -z "$OUTPUT_DIR" ]; then OUTPUT_DIR="."; fi
if [ ! -d "$OUTPUT_DIR" ]; then echo "Error: Output directory does not exist."; exit 1; fi
if ! [ -x "$(command -v convert)" ]; then
  echo "Error: convert not found. Check if ImageMagick is installed. Get it from https://www.imagemagick.org." >&2
  exit 1
fi

if [ -f $INPUT_IMAGE ]; then
  SIZES=(16 24 32 48 57 60 64 70 72 76 96 114 120 128 144 150 152 180 192 196 256 310 512)
  for size in ${SIZES[@]}; do
    convert -flatten -background none -resize ${size}x${size} $INPUT_IMAGE $OUTPUT_DIR/favicon-${size}.png
    if [ -f favicon-${size}.png ]; then
      echo $size px
    else
      echo "Error: Could not process input file. $INPUT_IMAGE may not be an image file."
      exit 1
    fi
  done
else
  echo "Error: Input file does not exist."
  exit 1
fi
