#!/bin/bash

# checking if sudo is used
if [ "$(id -u)" -ne 0 ]; then
    echo "ERROR: you must use sudo to run this script. Exiting..."
    exit 1
fi

docker exec ecommerce-web-1 bash -c "tail -f /var/log/apache2/error.log"