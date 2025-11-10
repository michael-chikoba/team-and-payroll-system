#!/bin/bash
echo "Building frontend..."

# Remove the dist folder completely
sudo rm -rf dist/

# Build as your regular user
npm run build

# Set proper permissions for web server access
sudo chown -R www-data:www-data dist/
sudo chmod -R 755 dist/

echo "Build completed successfully!"
