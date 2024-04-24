# Download WordPress core
if [ ! -d "wordpress" ]; then
    wp core download --path=wordpress
fi

# Set up the wp-config.php file
if [ ! -f "wordpress/wp-config.php" ]; then
    wp config create --dbname=wordpress --dbuser=root --dbpass=root --path=wordpress
fi

# Add ezeep credentials to wp-config.php
if ! grep -q "EZEEP_CLIENT_ID" wordpress/wp-config.php; then
    echo "define('EZEEP_CLIENT_ID', 'replace with the client id');" >> wordpress/wp-config.php
fi

if ! grep -q "EZEEP_CLIENT_SECRET" wordpress/wp-config.php; then
    echo "define('EZEEP_CLIENT_SECRET', 'replace with the client secret');" >> wordpress/wp-config.php
fi

# Clone the WooCommerce core into the plugins directory
if [ ! -d "wordpress/wp-content/plugins/woocommerce" ]; then
    git clone https://github.com/woocommerce/woocommerce.git wordpress/wp-content/plugins/woocommerce
fi
# Change to the WooCommerce directory
cd wordpress/wp-content/plugins/woocommerce

# Check if nvm is installed, if not install it

if ! command -v nvm &> /dev/null
then
    echo "nvm not found, installing..."
    curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.38.0/install.sh | bash
    source ~/.nvm/nvm.sh
fi

# Install the required Node version
nvm install 20.11.1
nvm use 20.11.1
npm install -g pnpm@8.12.1

# Check if composer is installed, if not install it
if ! command -v composer &> /dev/null
then
    echo "Composer not found, installing..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

# Install dependencies
pnpm install && composer install

# Build WooCommerce
pnpm build

# Change to the WordPress directory and start the PHP server
cd ../../..

if command -v php &> /dev/null
then
    php -S localhost:8000
else
    echo "PHP could not be found, please install it and try again"
    exit
fi
