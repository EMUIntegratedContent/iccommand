#!/bin/bash

# Change ownership
echo "SETTING UP IC COMMAND FILE OWNERSHIP..."
mkdir /var/www/html/var
chown -R www-data:www-data /var/www/html/var /var/www/html/public
echo "Finished setting up EMU Today file ownership!"
echo "SETTING UP IC COMMAND FILE PERMISSIONS..."

# Change permissions
chmod -R 775 /var/www/html/var /var/www/html/public
echo "Finished setting up IC Command file permissions!"

# Install all composer vendor packages and change the ownership and permissions of the directory
vendordir="/var/www/html/vendor"
if [ ! -d "$vendordir" ]; then
    echo "NO $vendordir DIRECTORY DETECTED. NOW INSTALLING THE PACKAGES..."
    mkdir "$vendordir"
    chown -R www-data:www-data "$vendordir"
    chmod -R 775 "$vendordir"
    echo "Directory $vendordir added successfully with proper ownership."
    cd /var/www/html
    composer self-update
    composer clear-cache
    composer update --prefer-dist --no-progress --no-interaction
    echo "Composer package installation complete."
fi

echo "YOU MAY NOW ACCESS IC COMMAND! HAVE FUN..."
# Start Apache in the foreground. This takes the place of "CMD ["apache2-foreground"]" in the Dockerfile!
exec apache2-foreground
