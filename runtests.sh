#!/usr/bin/env bash

ACTION=${1:-general}
RUNNER="ci/test/run-${ACTION}.sh"

if [[ ! -f "${RUNNER}" ]]; then
    echo "Testsuite runner ${RUNNER} does not exist!"
    exit 1
fi

$RUNNER
