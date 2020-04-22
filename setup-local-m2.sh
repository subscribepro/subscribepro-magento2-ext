#!/usr/bin/env bash

#
# This bash script is meant to be run from within the cloned Subscribe Pro extension repository
# It will download the specified Magento 2 version and mount the current extension files into
# that Magento codebase to allow us to use an IDE on the full Magento + SP Ext project
#

#
# Arguments passed to this script should be in the following order
# $1 = Magento 2 version to use
# $2 = location to install Magento
# $3 = URL host name (without protocol or slashes)
# $4 = Local URL (including protocol and port, no trailing slash)
#

# The ORIGINAL_DIR is the directory into which the SP extension has been cloned
ORIGINAL_DIR=`pwd`
echo "Setting up local Magento and SP extension for developer with Docker"

# Determine Magento version to get
# Have a default in case it's not specified in the script args
if [ $# -gt 0 ]
    then MAGENTO_VERSION_TO_INSTALL=$1
    else MAGENTO_VERSION_TO_INSTALL="2.3.4"
fi

if [ $# -gt 1 ]
    then MAGENTO_PATH_TO_INSTALL=$2
    else MAGENTO_PATH_TO_INSTALL="../local/m2"
fi

if [ $# -gt 2 ]
    then MAGENTO_BASE_HOSTNAME=$3
    else MAGENTO_BASE_HOSTNAME="local-m2.subscribepro.com"
fi

if [ $# -gt 3 ]
    then MAGENTO_LOCAL_URL=$4
    else MAGENTO_LOCAL_URL="http://localhost:8000"
fi

echo "Installing Magento $MAGENTO_VERSION_TO_INSTALL to $MAGENTO_PATH_TO_INSTALL"

# Get files into local directory to use for local development
# We will mount these files into the docker container

# If the app folder exists in the installation directory we will assume it is 
if [ ! -d $MAGENTO_PATH_TO_INSTALL/app/ ]
    then
        echo "Loading Magento files to $MAGENTO_PATH_TO_INSTALL directory..."
        mkdir -p $MAGENTO_PATH_TO_INSTALL
        cd $MAGENTO_PATH_TO_INSTALL
        # Download Magento 2
        COMPOSER_MEMORY_LIMIT=-1 composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition . $MAGENTO_VERSION_TO_INSTALL
        cd $ORIGINAL_DIR
        echo "done!"
    else
       echo "$MAGENTO_PATH_TO_INSTALL/app/ already exists, assuming Magento installed"
fi

if [ ! -d $MAGENTO_PATH_TO_INSTALL/vendor/subscribepro/ ]
    then
        # Install SP PHP SDK
        echo "Installing Subscribe Pro PHP SDK..."
        cd $MAGENTO_PATH_TO_INSTALL
        COMPOSER_MEMORY_LIMIT=-1 composer require subscribepro/subscribepro-php
        cd $ORIGINAL_DIR
        echo "done!"
    else
        echo "Subscribe Pro PHP SDK already installed"
fi

# If the Subscribe Pro extension isn't already installed to app/code/Swarming/SubscribePro, we add it
if [ ! -d $MAGENTO_PATH_TO_INSTALL/app/code/Swarming/SubscribePro ]
    then
        echo "Copying Subscribe Pro repository into $MAGENTO_PATH_TO_INSTALL/app/code/Swarming/SubscribePro..."
        mkdir -p $MAGENTO_PATH_TO_INSTALL/app/code/Swarming/SubscribePro && cp -r $ORIGINAL_DIR/. $MAGENTO_PATH_TO_INSTALL/app/code/Swarming/SubscribePro
        echo "You should set up your development environment based on that directory, not the current one."
        echo "done!"
    else
        echo "Subscribe Pro extension files already installed"
fi

# Set up docker for nginx reverse proxy and magento containers
docker-compose -f ./docker/nginx-proxy/docker-compose.yml up -d
docker-compose -f ./docker/magento/docker-compose.yml up -d

echo "Running cloudflared CLI to set up Argo tunnel"
# Set up web-facing access to local instance using Cloudflared CLI to set up an Argo tunnel
# This uses nohup ... & to run in the background so you will have to manually kill it if needed.
nohup cloudflared --hostname $MAGENTO_BASE_HOSTNAME $MAGENTO_LOCAL_URL >/dev/null 2>&1 &
echo "done!"

echo "Installing Magento..."
docker-compose -f ./docker/magento/docker-compose.yml exec magento chmod +x bin/magento
docker-compose -f ./docker/magento/docker-compose.yml exec magento bin/magento setup:install --db-host=db --db-name=magento --db-user=magento --db-password=magento --backend-frontname=admin --admin-user=admin --admin-password=password123 --admin-email=admin@example.com --admin-firstname=Admin --admin-lastname=Admin --base-url=http://$MAGENTO_BASE_HOSTNAME/ --base-url-secure=https://$MAGENTO_BASE_HOSTNAME/ --language=en_US --currency=USD --use-rewrites=1 --use-secure=1 --use-secure-admin=1 --cleanup-database
docker-compose -f ./docker/magento/docker-compose.yml exec magento bin/magento setup:upgrade
docker-compose -f ./docker/magento/docker-compose.yml exec magento rm -rf generated/metadata/* generated/code/*
docker-compose -f ./docker/magento/docker-compose.yml exec magento bin/magento deploy:mode:set developer
# Don't need to run setup:di:compile for developer mode
#docker-compose -f ./docker/magento/docker-compose.yml exec magento bin/magento setup:di:compile
docker-compose -f ./docker/magento/docker-compose.yml exec magento bin/magento cache:flush
echo "done!"

echo 'Finished running setup-local-m2.sh'
