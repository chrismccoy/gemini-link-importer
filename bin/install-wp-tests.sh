#!/bin/bash
#
# Script to install the WordPress unit testing framework.
#
# Credits:
#   Based on the core script by Nikolay Bachiyski / Automattic
#   Inspired by the version from a Yoast WordPress SEO plugin
#

# Path to the WordPress unit testing utilities from Yoast
WP_TEST_UTILS_PATH="vendor/yoast/wp-test-utils/inc/"

# Check if the path exists
if [ ! -d "$WP_TEST_UTILS_PATH" ]; then
    echo "Yoast WP Test Utils not found at $WP_TEST_UTILS_PATH. Did you run 'composer install'?"
    exit 1
fi

# Call the actual installation script from Yoast's package
bash "${WP_TEST_UTILS_PATH}install-wp-tests.sh" "$@"
