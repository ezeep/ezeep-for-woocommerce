FROM wordpress:latest

# Install WP-CLI and other necessary tools
RUN apt-get update && apt-get install -y less curl sudo \
    && curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy initialization script
COPY init-wordpress.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/init-wordpress.sh

# Set the entrypoint
ENTRYPOINT ["init-wordpress.sh"]
CMD ["apache2-foreground"]
