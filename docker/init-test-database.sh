#!/usr/bin/env sh

set -eu

psql --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" --set=ON_ERROR_STOP=1 \
    --command 'CREATE DATABASE autoconf_test;'
