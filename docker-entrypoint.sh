#!/bin/bash
set -euo pipefail

# Execute the default WordPress entrypoint script
/usr/local/bin/docker-entrypoint.sh apache2-foreground &

# Wait for WordPress to be ready
until $(wp core is-installed --allow-root); do
  sleep 1
done

# Install and activate WooCommerce if not already installed
if ! $(wp plugin is-installed woocommerce --allow-root); then
  wp plugin install woocommerce --activate --allow-root
fi

# Keep the container running
wait
