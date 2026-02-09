#!/bin/bash
echo "Building frontend for mobile..."

# Remove the dist folder completely
sudo rm -rf dist/

# Build as your regular user
npm run build

# Sync with Capacitor
npx cap sync

# Set proper permissions for web server access
sudo chown -R www-data:www-data dist/
sudo chmod -R 755 dist/

echo "Mobile build completed successfully!"
echo "To open in Android Studio: npx cap open android"