#!/bin/sh -ex

apt-get update
apt-get install --no-install-recommends --no-install-suggests -y "$@"
rm -r /var/lib/apt/lists/*
