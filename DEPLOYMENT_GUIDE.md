# Deployment Guide: DIDX Laravel to Ubuntu Server

## Prerequisites on Ubuntu Server

```bash
# Update system
sudo apt update
sudo apt upgrade -y

# Install PHP and required extensions
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath

# Install MySQL client
sudo apt install -y mysql-client

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Git
sudo apt install -y git

# Install Nginx (or Apache)
sudo apt install -y nginx
# OR
# sudo apt install -y apache2 libapache2-mod-php8.2
```

---

## Step 1: Upload Project to Ubuntu Server

### Option A: Using Git (Recommended)

```bash
# On your Ubuntu server, navigate to web root
cd /var/www

# Clone the repository (if using Git)
sudo git clone <your-repo-url> didx-laravel

# OR copy from local machine using SCP
scp -r c:\xampp\htdocs\didx-laravel your-username@your-server-ip:/var/www/
```

### Option B: Using SCP (Direct File Transfer)

```bash
# On Windows, use PowerShell:
scp -r C:\xampp\htdocs\didx-laravel your-username@your-server-ip:/var/www/

# Example:
scp -r C:\xampp\htdocs\didx-laravel admin@192.168.x.x:/var/www/
```

---

## Step 2: Set Permissions

```bash
# Navigate to project
cd /var/www/didx-laravel

# Set ownership
sudo chown -R www-data:www-data /var/www/didx-laravel

# Set permissions
sudo chmod -R 755 /var/www/didx-laravel
sudo chmod -R 775 /var/www/didx-laravel/storage
sudo chmod -R 775 /var/www/didx-laravel/bootstrap/cache
```

---

## Step 3: Install Dependencies

```bash
cd /var/www/didx-laravel

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# If you need development dependencies:
composer install
```

---

## Step 4: Configure Environment

```bash
# Copy .env file
cp .env.example .env

# Or edit existing .env
nano .env
```

**Update these in `.env`:**

```ini
APP_NAME=DIDX
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server-ip

DB_CONNECTION=mysql
DB_HOST=165.227.88.28
DB_PORT=3306
DB_DATABASE=telecom_db
DB_USERNAME=admin
DB_PASSWORD=12343211

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

---

## Step 5: Generate Application Key

```bash
php artisan key:generate

# Clear cache
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

---

## Step 6: Run Migrations (if needed)

```bash
# Create migrations table
php artisan migrate

# Or if you want to refresh:
php artisan migrate:fresh
```

---

## Step 7: Configure Nginx (Recommended for Ubuntu)

### Create Nginx Config

```bash
sudo nano /etc/nginx/sites-available/didx-laravel
```

**Paste this configuration:**

```nginx
server {
    listen 80;
    server_name your-server-ip;

    root /var/www/didx-laravel/public;
    index index.php index.html index.htm;

    # Logs
    access_log /var/log/nginx/didx-laravel-access.log;
    error_log /var/log/nginx/didx-laravel-error.log;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~ ^/storage/ {
        try_files $uri =404;
    }
}
```

### Enable Nginx Site

```bash
sudo ln -s /etc/nginx/sites-available/didx-laravel /etc/nginx/sites-enabled/

# Test Nginx configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

---

## Step 8: Configure Apache (Alternative to Nginx)

### Enable mod_rewrite

```bash
sudo a2enmod rewrite
sudo a2enmod php8.2
```

### Create VirtualHost

```bash
sudo nano /etc/apache2/sites-available/didx-laravel.conf
```

**Paste this:**

```apache
<VirtualHost *:80>
    ServerName your-server-ip
    ServerAdmin admin@example.com
    DocumentRoot /var/www/didx-laravel/public

    <Directory /var/www/didx-laravel>
        AllowOverride All
        Require all granted
    </Directory>

    <Directory /var/www/didx-laravel/public>
        AllowOverride All
        Require all granted
        
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^ index.php [QSA,L]
        </IfModule>
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/didx-laravel-error.log
    CustomLog ${APACHE_LOG_DIR}/didx-laravel-access.log combined
</VirtualHost>
```

### Enable Site

```bash
sudo a2ensite didx-laravel.conf
sudo apache2ctl configtest
sudo systemctl restart apache2
```

---

## Step 9: Test the Application

```bash
# Start Laravel development server (for testing)
php artisan serve --host=0.0.0.0 --port=8000

# OR visit in browser
http://your-server-ip
http://your-server-ip:8000/dashboard
```

---

## Step 10: Set Up SSL (Optional but Recommended)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Generate SSL certificate
sudo certbot certonly --nginx -d your-domain.com

# Update Nginx config to use SSL (add to server block)
```

---

## Step 11: Set Up Auto-Start with Systemd (Optional)

Create a service file for Laravel queue (if using):

```bash
sudo nano /etc/systemd/system/didx-laravel-queue.service
```

```ini
[Unit]
Description=DIDX Laravel Queue
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/didx-laravel
ExecStart=/usr/bin/php /var/www/didx-laravel/artisan queue:work
Restart=always

[Install]
WantedBy=multi-user.target
```

Then enable:

```bash
sudo systemctl enable didx-laravel-queue.service
sudo systemctl start didx-laravel-queue.service
```

---

## Troubleshooting

### 502 Bad Gateway (Nginx)

```bash
# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Check Nginx error log
sudo tail -f /var/log/nginx/didx-laravel-error.log
```

### Permission Denied

```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/didx-laravel
sudo find /var/www/didx-laravel -type f -exec chmod 644 {} \;
sudo find /var/www/didx-laravel -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/didx-laravel/storage
```

### Database Connection Failed

```bash
# Test MySQL connection
mysql -h 165.227.88.28 -u admin -p12343211 -e "SELECT 1"

# Check .env file
cat /var/www/didx-laravel/.env | grep DB_
```

### Composer Install Issues

```bash
# Increase PHP memory limit
php -d memory_limit=512M /usr/local/bin/composer install

# Clear composer cache
composer clear-cache
```

---

## Quick Deployment Checklist

- [ ] Server has PHP 8.2+ installed
- [ ] Composer installed
- [ ] Git installed (if using git clone)
- [ ] Project files uploaded to /var/www/didx-laravel
- [ ] Permissions set correctly (www-data owner, 775 for storage)
- [ ] .env file configured with database credentials
- [ ] `composer install` executed
- [ ] `php artisan key:generate` executed
- [ ] `php artisan migrate` executed (if first time)
- [ ] Nginx or Apache configured
- [ ] Web server restarted
- [ ] Can access http://your-server-ip/dashboard
- [ ] Database connection working
- [ ] API endpoint responds: http://your-server-ip/api/status

---

## Post-Deployment

```bash
# Enable production mode
# In .env set: APP_ENV=production and APP_DEBUG=false

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check application status
php artisan tinker
> DB::connection()->getPdo()
```

---

## Useful Commands

```bash
# Check application logs
tail -f /var/www/didx-laravel/storage/logs/laravel.log

# Clear all caches
php artisan cache:clear && php artisan view:clear && php artisan config:clear

# Check database
mysql -h 165.227.88.28 -u admin -p12343211 telecom_db -e "SHOW TABLES;"

# Restart web services
sudo systemctl restart nginx
# OR
sudo systemctl restart apache2
sudo systemctl restart php8.2-fpm
```

---

## Summary

Your DIDX Laravel application is now running on Ubuntu! 🚀

- **Dashboard**: http://your-server-ip/dashboard
- **API Status**: http://your-server-ip/api/status
- **Database**: Remote MySQL at 165.227.88.28
- **Auto-update**: Every 3 seconds (no reload needed)
