#!/usr/bin/env bash

BASEDIR=$(dirname $0)

source ${BASEDIR}/functions
PROJECTDIR="${BASEDIR}/../"

cd ${PROJECTDIR}

echoTitle "******** Docker Image Building ********"

TAG=$(git describe --tags --abbrev=0)
docker buildx build --build-arg BUILDKIT_INLINE_CACHE=1 --platform linux/arm64,linux/amd64 --cache-from sklimaszewski/sflaunchpad:latest --tag sklimaszewski/sflaunchpad:latest --tag sklimaszewski/sflaunchpad:${TAG} --push --file Dockerfile .

echoSuccess "Done."

exit 0;
