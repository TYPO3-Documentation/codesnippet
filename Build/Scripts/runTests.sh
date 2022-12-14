#!/usr/bin/env bash

#
# TYPO3 core test runner based on docker and docker-compose.
#

# Function to write a .env file in Build/testing-docker
# This is read by docker-compose and vars defined here are
# used in Build/testing-docker/docker-compose.yml
setUpDockerComposeDotEnv() {
    # Delete possibly existing local .env file if exists
    [ -e .env ] && rm .env
    # Set up a new .env file for docker-compose
    {
        echo "COMPOSE_PROJECT_NAME=local"
        # To prevent access rights of files created by the testing, the docker image later
        # runs with the same user that is currently executing the script. docker-compose can't
        # use $UID directly itself since it is a shell variable and not an env variable, so
        # we have to set it explicitly here.
        echo "HOST_UID=`id -u`"
        # Your local user
        echo "ROOT_DIR=${ROOT_DIR}"
        echo "HOST_USER=${USER}"
        echo "TEST_FILE=${TEST_FILE}"
        echo "TYPO3_VERSION=${TYPO3_VERSION}"
        echo "PHP_XDEBUG_ON=${PHP_XDEBUG_ON}"
        echo "PHP_XDEBUG_PORT=${PHP_XDEBUG_PORT}"
        echo "DOCKER_PHP_IMAGE=${DOCKER_PHP_IMAGE}"
        echo "EXTRA_TEST_OPTIONS=${EXTRA_TEST_OPTIONS}"
        echo "SCRIPT_VERBOSE=${SCRIPT_VERBOSE}"
        echo "CGLCHECK_DRY_RUN=${CGLCHECK_DRY_RUN}"
        echo "COMPOSER_NORMALIZE_DRY_RUN=${COMPOSER_NORMALIZE_DRY_RUN}"
    } > .env
}

# Load help text into $HELP
read -r -d '' HELP <<EOF
EXT:examples test runner. Check code styles, lint PHP files and some other details.

Recommended docker version is >=20.10 for xdebug break pointing to work reliably, and
a recent docker-compose (tested >=1.21.2) is needed.

Usage: $0 [options] [file]

No arguments: Run all unit tests with PHP 8.1

Options:
    -s <...>
        Specifies which test suite to run
            - cgl: cgl test and fix all php files
            - clean: Cleanup non-repo files from testing
            - composerNormalize: "composer normalize"
            - composerUpdate: "composer update", handy if host has no PHP
            - composerValidate: "composer validate"
            - lint: PHP linting
            - unit (default): PHP unit tests

    -p <8.1|8.2>
        Specifies the PHP minor version to be used
            - 8.1 (default): use PHP 8.1
            - 8.2: use PHP 8.2

    -e "<phpunit additional scan options>"
        Only with -s unit
        Additional options to send to phpunit (unit tests).
        Options starting with "--" must be added after options starting with "-".
        Example -e "-v --filter canRetrieveValueWithGP" to enable verbose output AND filter tests
        named "canRetrieveValueWithGP"

    -t <11.5|12.0|main>
        Only with -s composerUpdate
        Specifies the TYPO3 core major version to be used
            - 11.5 (default): use TYPO3 core v10
            - 12.0: use TYPO3 core v12.0
            - main: use TYPO3 core main

    -x
        Only with -s unit|acceptance
        Send information to host instance for test or system under test break points. This is especially
        useful if a local PhpStorm instance is listening on default xdebug port 9003. A different port
        can be selected with -y

    -y <port>
        Send xdebug information to a different port than default 9003 if an IDE like PhpStorm
        is not listening on default port.

    -n
        Only with -s cgl, composerNormalize
        Activate dry-run in CGL check and composer normalize that does not actively change files and only prints broken ones.

    -u
        Update existing typo3/core-testing-*:latest docker images. Maintenance call to docker pull latest
        versions of the main php images. The images are updated once in a while and only the youngest
        ones are supported by core testing. Use this if weird test errors occur. Also removes obsolete
        image versions of typo3/core-testing-*.

    -v
        Enable verbose script output. Shows variables and docker commands.

    -h
        Show this help.

Examples:
    # Run unit tests using PHP 8.1
    ./Build/Scripts/runTests.sh
EOF

# Test if docker-compose exists, else exit out with error
if ! type "docker-compose" > /dev/null; then
  echo "This script relies on docker and docker-compose. Please install" >&2
  exit 1
fi

# Go to the directory this script is located, so everything else is relative
# to this dir, no matter from where this script is called.
THIS_SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"
cd "$THIS_SCRIPT_DIR" || exit 1

# Go to directory that contains the local docker-compose.yml file
cd ../testing-docker || exit 1

# Option defaults
if ! command -v realpath &> /dev/null; then
  echo "This script works best with realpath installed" >&2
  ROOT_DIR="${PWD}/../../"
else
  ROOT_DIR=`realpath ${PWD}/../../`
fi
TEST_SUITE="cgl"
PHP_VERSION="8.1"
TYPO3_VERSION="11.5"
PHP_XDEBUG_ON=0
PHP_XDEBUG_PORT=9003
SCRIPT_VERBOSE=0
CGLCHECK_DRY_RUN=""
COMPOSER_NORMALIZE_DRY_RUN=""

# Option parsing
# Reset in case getopts has been used previously in the shell
OPTIND=1
# Array for invalid options
INVALID_OPTIONS=();
# Simple option parsing based on getopts (! not getopt). GNU getopts is available on linux and mac systems,
# where GNU getop may be not installed on mac systems.
while getopts ":s:p:t:e:xynhuv" OPT; do
    case ${OPT} in
        e)
            EXTRA_TEST_OPTIONS=${OPTARG}
            ;;
        h)
            echo "${HELP}"
            exit 0
            ;;
        s)
            TEST_SUITE=${OPTARG}
            ;;
        t)
            TYPO3_VERSION=${OPTARG}
            if ! [[ ${TYPO3_VERSION} =~ ^(11.5|12.0|main)$ ]]; then
                INVALID_OPTIONS+=("t ${OPTARG}")
            fi
            ;;
        n)
            CGLCHECK_DRY_RUN="-n"
            COMPOSER_NORMALIZE_DRY_RUN="--dry-run"
            ;;
        p)
            PHP_VERSION=${OPTARG}
            if ! [[ ${PHP_VERSION} =~ ^(8.1|8.2)$ ]]; then
                INVALID_OPTIONS+=("p ${OPTARG}")
            fi
            ;;
        u)
            TEST_SUITE=update
            ;;
        v)
            SCRIPT_VERBOSE=1
            ;;
        x)
            PHP_XDEBUG_ON=1
            ;;
        y)
            PHP_XDEBUG_PORT=${OPTARG}
            ;;
        \?)
            INVALID_OPTIONS+=(${OPTARG})
            ;;
        :)
            INVALID_OPTIONS+=(${OPTARG})
            ;;
    esac
done

# Exit on invalid options
if [ ${#INVALID_OPTIONS[@]} -ne 0 ]; then
    echo "Invalid option(s):" >&2
    for I in "${INVALID_OPTIONS[@]}"; do
        echo "-"${I} >&2
    done
    echo >&2
    echo "${HELP}" >&2
    exit 1
fi

# Move "8.1" to "php81", the latter is the docker container name
DOCKER_PHP_IMAGE=`echo "php${PHP_VERSION}" | sed -e 's/\.//'`

# Set $1 to first mass argument, this is the optional test file or test directory to execute
shift $((OPTIND - 1))
TEST_FILE=${1}
if [ -n "${1}" ]; then
    TEST_FILE="Web/typo3conf/ext/codesnippet/${1}"
fi

if [ ${SCRIPT_VERBOSE} -eq 1 ]; then
    set -x
fi

# Suite execution
case ${TEST_SUITE} in
    cgl)
        # Active dry-run for cgl needs not "-n" but specific options
        if [[ ! -z ${CGLCHECK_DRY_RUN} ]]; then
            CGLCHECK_DRY_RUN="--dry-run --diff"
        fi
        setUpDockerComposeDotEnv
        docker-compose run cgl
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    clean)
        rm -rf \
          ../../composer.lock \
          ../../.Build/ \
          ../../.cache/ \
          ../../composer.json.testing \
          ../../var/
        ;;
    composerNormalize)
        setUpDockerComposeDotEnv
        docker-compose run composer_normalize
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    composerUpdate)
        setUpDockerComposeDotEnv
        cp ../../composer.json ../../composer.json.orig
        if [ -f "../../composer.json.testing" ]; then
            cp ../../composer.json ../../composer.json.orig
        fi
        docker-compose run composer_update
        cp ../../composer.json ../../composer.json.testing
        mv ../../composer.json.orig ../../composer.json
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    composerValidate)
        setUpDockerComposeDotEnv
        docker-compose run composer_validate
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    lint)
        setUpDockerComposeDotEnv
        docker-compose run lint
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    unit)
        setUpDockerComposeDotEnv
        docker-compose run unit
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    update)
        # pull typo3/core-testing-*:latest versions of those ones that exist locally
        docker images typo3/core-testing-*:latest --format "{{.Repository}}:latest" | xargs -I {} docker pull {}
        # remove "dangling" typo3/core-testing-* images (those tagged as <none>)
        docker images typo3/core-testing-* --filter "dangling=true" --format "{{.ID}}" | xargs -I {} docker rmi {}
        ;;
    *)
        echo "Invalid -s option argument ${TEST_SUITE}" >&2
        echo >&2
        echo "${HELP}" >&2
        exit 1
esac

exit $SUITE_EXIT_CODE
