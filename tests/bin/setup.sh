#!/usr/bin/env bash

if [[ $(docker ps -f 'name=datastore' --format '{{.Names}}') != 'datastore' ]]; then
    docker run -d -p 8282:8282 --name datastore egymgmbh/datastore-emulator:latest;
fi;