#!/bin/bash
set -euo pipefail

# Run the default entrypoint script
/usr/local/bin/docker-entrypoint.sh apache2-foreground &

# Wait for WordPress to be able to connect to the database
until wp core is-installed --allow-root 2>/dev/null || wp db check --allow-root 2>/dev/null; do
    echo "Waiting for WordPress installation or database connection..."
    sleep 5
done

# Install and activate WooCommerce if not already installed
if ! wp plugin is-installed woocommerce --allow-root; then
    echo "Installing and activating WooCommerce..."
    wp plugin install woocommerce --activate --allow-root
fi

echo "WordPress and WooCommerce are ready!"

# Keep the container running
wait

